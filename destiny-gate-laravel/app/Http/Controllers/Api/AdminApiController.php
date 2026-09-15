<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Application;
use App\Models\ApplicationDeposit;
use App\Support\BillGenerationService;
use App\Support\StudentNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminApiController extends Controller
{
    public function dashboard()
    {
        $currentTerm = DB::table('terms')->where('is_current', true)->first();

        return response()->json([
            // Kept for any other consumer of this endpoint — the dashboard UI itself
            // reads the fields below.
            'total_users'    => User::count(),
            'total_classes'  => SchoolClass::count(),
            'recent_users'   => User::latest()->take(5)->get(['id','name','email','role','created_at']),

            'admin_name'      => auth()->user()->name,
            'total_students'  => Student::where('status', 'active')->count(),
            'total_staff'     => Staff::where('is_active', true)->count(),
            'pending_apps'    => Application::where('status', 'pending')->where('is_draft', false)->count(),
            'owing_students'  => DB::table('student_bills')->whereIn('status', ['unpaid', 'partial'])->distinct()->count('student_id'),
            'unbilled'        => $this->dashboardUnbilledStudents($currentTerm),
            'fees_this_term'  => $this->dashboardFeesThisTerm($currentTerm),
            'attendance_rate' => $this->dashboardAttendanceRate(),
            'monthly_collections' => $this->dashboardMonthlyCollections(),
            'top_classes'         => $this->dashboardTopClasses($currentTerm),
            'attendance_breakdown'=> $this->dashboardAttendanceBreakdown(),
            'recent_activities'   => $this->dashboardRecentActivities(),
            'reminders'           => $this->dashboardReminders($currentTerm),
            'pending_actions'     => $this->dashboardPendingActions(),
        ]);
    }

    /** Active students with zero bill rows for the current term — see the whole "no bill
     *  ≠ fully paid" investigation this billing module was built around. */
    private function dashboardUnbilledStudents(?object $term): array
    {
        if (!$term) return ['count' => 0, 'students' => []];

        $billedIds = DB::table('student_bills')
            ->where('academic_year_id', $term->academic_year_id)
            ->where('term_id', $term->id)
            ->where('status', '!=', 'cancelled')
            ->distinct()->pluck('student_id');

        $unbilled = DB::table('students')
            ->where('status', 'active')
            ->whereNotIn('id', $billedIds)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'student_number', 'admission_number']);

        return [
            'count'    => $unbilled->count(),
            'students' => $unbilled->take(8)->map(fn ($s) => [
                'id'     => $s->id,
                'name'   => trim("{$s->first_name} {$s->last_name}"),
                'number' => $s->student_number ?? $s->admission_number,
            ])->values(),
        ];
    }

    private function dashboardFeesThisTerm(?object $term): array
    {
        if (!$term) return ['collected' => 0.0, 'expected' => 0.0];
        return [
            'collected' => (float) DB::table('finance_payments')->where('academic_year_id', $term->academic_year_id)->where('term_id', $term->id)->where('status', 'active')->sum('amount'),
            'expected'  => (float) DB::table('student_bills')->where('academic_year_id', $term->academic_year_id)->where('term_id', $term->id)->where('status', '!=', 'cancelled')->sum('amount'),
        ];
    }

    /** Percentage of 'present' among all attendance records ever taken — null if none exist yet. */
    private function dashboardAttendanceRate(): ?float
    {
        $total = DB::table('student_attendance_records')->count();
        if ($total === 0) return null;
        $present = DB::table('student_attendance_records')->where('status', 'present')->count();
        return round($present / $total * 100, 1);
    }

    private function dashboardAttendanceBreakdown(): array
    {
        $rows = DB::table('student_attendance_records')
            ->select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');
        $total = $rows->sum();
        if ($total === 0) return [];

        $colors = ['present' => '#1a6b3c', 'late' => '#f59e0b', 'absent' => '#ef4444', 'excused' => '#2563eb', 'sick' => '#7c3aed', 'early_departure' => '#9ca3af'];
        $out = [];
        foreach ($rows as $status => $cnt) {
            $out[] = ['name' => ucfirst(str_replace('_', ' ', $status)), 'value' => round($cnt / $total * 100, 1), 'color' => $colors[$status] ?? '#9ca3af'];
        }
        return $out;
    }

    /** Last 6 calendar months of real fee collections (finance_payments) vs. amounts billed in that same month. */
    private function dashboardMonthlyCollections(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $collected = DB::table('finance_payments')
            ->where('status', 'active')
            ->where('payment_date', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $billed = DB::table('student_bills')
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        return array_map(fn ($ym) => [
            'month'     => date('M', strtotime("{$ym}-01")),
            'collected' => round((float) ($collected[$ym] ?? 0), 2),
            'target'    => round((float) ($billed[$ym] ?? 0), 2),
        ], $months);
    }

    /** Per-class (stream) average for the current term, from real results — empty until marks exist. */
    private function dashboardTopClasses(?object $term): array
    {
        if (!$term) return [];
        $rows = DB::table('results_aggregates as ra')
            ->join('streams as st', 'ra.stream_id', '=', 'st.id')
            ->join('forms as f', 'st.form_id', '=', 'f.id')
            ->where('ra.academic_year_id', $term->academic_year_id)
            ->where('ra.term_id', $term->id)
            ->whereNotNull('ra.term_average')
            ->select('st.id', DB::raw("CONCAT(f.name, ' ', st.name) as name"), DB::raw('AVG(ra.term_average) as avg'))
            ->groupBy('st.id', 'f.name', 'st.name')
            ->orderByDesc('avg')
            ->limit(5)
            ->get();

        return $rows->map(fn ($r) => ['name' => $r->name, 'pct' => round((float) $r->avg, 1)])->values()->all();
    }

    /** Merges recently created students, payments received, and behaviour issues into one real feed. */
    private function dashboardRecentActivities(): array
    {
        $events = [];

        foreach (DB::table('students')->orderByDesc('created_at')->limit(5)->get(['first_name', 'last_name', 'created_at']) as $s) {
            $events[] = ['icon' => '👤', 'bg' => '#f0faf4', 'text' => "New student registered: {$s->first_name} {$s->last_name}", 'at' => $s->created_at];
        }
        foreach (DB::table('finance_payments as fp')->join('students as s', 'fp.student_id', '=', 's.id')->orderByDesc('fp.created_at')->limit(5)->get(['s.first_name', 's.last_name', 'fp.amount', 'fp.created_at']) as $p) {
            $events[] = ['icon' => '💰', 'bg' => '#fffbeb', 'text' => "Payment of \${$p->amount} received from {$p->first_name} {$p->last_name}", 'at' => $p->created_at];
        }
        foreach (DB::table('behaviour_records as br')->join('students as s', 'br.student_id', '=', 's.id')->orderByDesc('br.created_at')->limit(5)->get(['s.first_name', 's.last_name', 'br.issue_type', 'br.created_at']) as $b) {
            $events[] = ['icon' => '⚠️', 'bg' => '#fef2f2', 'text' => "Behaviour record logged for {$b->first_name} {$b->last_name}: {$b->issue_type}", 'at' => $b->created_at];
        }
        foreach (DB::table('applications')->orderByDesc('created_at')->limit(5)->get(['first_name', 'last_name', 'created_at']) as $a) {
            $events[] = ['icon' => '📝', 'bg' => '#eff6ff', 'text' => "New admission application: {$a->first_name} {$a->last_name}", 'at' => $a->created_at];
        }

        usort($events, fn ($a, $b) => strtotime($b['at']) <=> strtotime($a['at']));
        return array_map(fn ($e) => ['icon' => $e['icon'], 'bg' => $e['bg'], 'text' => $e['text'], 'time' => \Carbon\Carbon::parse($e['at'])->diffForHumans()], array_slice($events, 0, 6));
    }

    /** Real, derived reminders — no fake calendar events. */
    private function dashboardReminders(?object $term): array
    {
        $reminders = [];

        $owing = DB::table('student_bills')->whereIn('status', ['unpaid', 'partial'])->distinct()->count('student_id');
        if ($owing > 0) $reminders[] = ['icon' => '⚠️', 'text' => "{$owing} student(s) have outstanding fees", 'date' => null];

        $pendingApps = Application::where('status', 'pending')->where('is_draft', false)->count();
        if ($pendingApps > 0) $reminders[] = ['icon' => '📥', 'text' => "{$pendingApps} admission application(s) awaiting review", 'date' => null];

        if ($term) {
            $reminders[] = ['icon' => '📅', 'text' => "{$term->name} ends", 'date' => date('M j, Y', strtotime($term->end_date))];
        }

        $dueSoon = DB::table('student_bills')->whereIn('status', ['unpaid', 'partial'])->whereNotNull('due_date')->whereBetween('due_date', [now()->toDateString(), now()->addDays(14)->toDateString()])->count();
        if ($dueSoon > 0) $reminders[] = ['icon' => '📌', 'text' => "{$dueSoon} bill(s) due within 14 days", 'date' => null];

        return $reminders;
    }

    /** Replaces the old fake "Notifications" panel with real things that actually need doing. */
    private function dashboardPendingActions(): array
    {
        $actions = [];

        $unverified = DB::table('students')->where('status', 'active')->whereNull('document_verified_at')->count();
        if ($unverified > 0) $actions[] = ['dot' => '#f59e0b', 'text' => "{$unverified} student(s) awaiting document verification"];

        $unmapped = DB::table('students')->where('status', 'active')->whereNull('stream_id')->whereNull('class_id')->count();
        if ($unmapped > 0) $actions[] = ['dot' => '#ef4444', 'text' => "{$unmapped} student(s) not yet placed in a class"];

        $noGuardian = DB::table('students as s')->where('s.status', 'active')->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('guardians as g')->whereColumn('g.student_id', 's.id'))->count();
        if ($noGuardian > 0) $actions[] = ['dot' => '#2563eb', 'text' => "{$noGuardian} student(s) have no guardian on file"];

        return $actions;
    }

    public function users(Request $request)
    {
        $users = User::latest()->paginate(20);
        return response()->json($users);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,headmaster,teacher,bursar,parent,student,user',
        ]);
        $user->update(['role' => $request->role]);
        return response()->json(['message' => "Role updated to {$request->role}.", 'user' => $user]);
    }

    public function staff()
    {
        $staff = Staff::with('user')->where('is_active', true)->paginate(20);
        return response()->json($staff);
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|max:20',
            'department'      => 'nullable|string|max:100',
            'position'        => 'nullable|string|max:100',
            'qualifications'  => 'nullable|string',
            'employment_date' => 'nullable|date',
            'role'            => 'required|in:teacher,bursar,headmaster,admin',
        ]);

        $user = User::create([
            'name'     => "{$data['first_name']} {$data['last_name']}",
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'role'     => $data['role'],
            'password' => Hash::make('password123'),
        ]);

        $staffId = 'STF-' . str_pad(Staff::count() + 1, 4, '0', STR_PAD_LEFT);

        $staff = Staff::create([
            'user_id'         => $user->id,
            'staff_id'        => $staffId,
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'department'      => $data['department'] ?? null,
            'position'        => $data['position'] ?? null,
            'qualifications'  => $data['qualifications'] ?? null,
            'employment_date' => $data['employment_date'] ?? null,
            'roles'           => [$data['role']],
        ]);

        return response()->json(['message' => 'Staff created.', 'staff' => $staff], 201);
    }

    public function classes()
    {
        $classes = SchoolClass::with('classTeacher')->paginate(20);
        return response()->json($classes);
    }

    public function storeClass(Request $request)
    {
        $data = $request->validate([
            'class_name'       => 'required|string|max:100',
            'stream'           => 'nullable|string|max:50',
            'class_teacher_id' => 'nullable|exists:staff,id',
            'academic_year'    => 'required|string|max:20',
            'capacity'         => 'nullable|integer|min:1',
        ]);
        $class = SchoolClass::create($data);
        return response()->json(['message' => 'Class created.', 'class' => $class], 201);
    }

    public function applications()
    {
        $apps = Application::where('is_draft', false)->latest()->paginate(20);
        return response()->json($apps);
    }

    public function documentRequests(Application $application)
    {
        return response()->json(
            DB::table('application_document_requests')
                ->where('application_id', $application->id)
                ->orderByDesc('id')
                ->get()
        );
    }

    public function requestDocumentResubmission(Request $request, Application $application)
    {
        $data = $request->validate([
            'document_key' => 'required|string|in:doc_student_id_path,doc_results_path,doc_parent_id_path,doc_transfer_letter_path',
            'instructions' => 'nullable|string|max:2000',
        ]);

        $exists = DB::table('application_document_requests')
            ->where('application_id', $application->id)
            ->where('document_key', $data['document_key'])
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'A pending request already exists for this document.'], 422);
        }

        $oldPath = $application->{$data['document_key']} ?? null;

        $id = DB::table('application_document_requests')->insertGetId([
            'application_id' => $application->id,
            'document_key' => $data['document_key'],
            'status' => 'pending',
            'instructions' => $data['instructions'] ?? null,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'old_path' => $oldPath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('application_document_requests')->where('id', $id)->first(), 201);
    }

    public function approveApplication(Request $request, Application $application)
    {
        return $this->offerApplication($application);
    }

    public function offerApplication(Application $application)
    {
        $token = $application->offer_letter_token;
        if (!$token) {
            do {
                $token = Str::upper(Str::random(16));
            } while (DB::table('applications')->where('offer_letter_token', $token)->exists());
        }

        $application->update([
            'status'       => 'offered',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'offer_letter_token' => $token,
            'offer_letter_expires_at' => now()->addDays(21),
        ]);

        return response()->json(['message' => 'Place offered.', 'application' => $application]);
    }

    public function waitlistApplication(Application $application)
    {
        $application->update([
            'status'       => 'waiting_list',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return response()->json(['message' => 'Application moved to waiting list.', 'application' => $application]);
    }

    public function rejectApplication(Request $request, Application $application)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        $application->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'processed_by'     => auth()->id(),
            'processed_at'     => now(),
        ]);
        return response()->json(['message' => 'Application rejected.', 'application' => $application]);
    }

    public function updateApplicationIntake(Request $request, Application $application)
    {
        $data = $request->validate([
            'intended_class' => 'nullable|string|max:100',
            'form_id'        => 'nullable|exists:forms,id',
            'category_id'    => 'nullable|exists:categories,id',
        ]);

        $application->update($data);

        return response()->json(['message' => 'Application updated.', 'application' => $application]);
    }

    /**
     * A Student record only ever gets created here, once a deposit is recorded
     * against an offered application. This is the sole enrollment path.
     */
    public function recordDepositAndEnroll(Request $request, Application $application)
    {
        $data = $request->validate([
            'amount'              => 'required|numeric|min:0.01',
            'payment_method'      => 'required|in:cash,ecocash,visa,mastercard,omari,innbucks,bank_transfer,other',
            'reference_number'    => 'nullable|string|max:100',
            'payment_date'        => 'required|date',
            'verification_due_at' => 'required|date',
            'notes'               => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data, $application) {
            $locked = Application::where('id', $application->id)->lockForUpdate()->first();

            if ($locked->status !== 'offered') {
                throw ValidationException::withMessages([
                    'status' => 'Only offered applications can be enrolled. Current status: ' . $locked->status,
                ]);
            }

            if ($locked->enrolled_student_id) {
                throw ValidationException::withMessages([
                    'status' => 'This application has already been enrolled.',
                ]);
            }

            $student = $this->createStudentFromApplication($locked, $data);

            // Bills this student against whatever fee structures apply to their form for the
            // current term — same trigger as StudentApiController::store(), so admissions
            // enrollments get billed exactly like manually added students do. Left to resolve
            // the current term itself (rather than passing $locked->academic_year_id) so the
            // year/term pairing used to match fee structures stays internally consistent.
            BillGenerationService::autoGenerateForStudent($student->id);

            $deposit = ApplicationDeposit::create([
                'application_id'   => $locked->id,
                'amount'           => $data['amount'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'received_by'      => auth()->id(),
                'payment_date'     => $data['payment_date'],
                'notes'            => $data['notes'] ?? null,
            ]);

            $this->createGuardiansFromApplication($locked, $student);

            $locked->update([
                'status'              => 'enrolled',
                'enrolled_student_id' => $student->id,
            ]);

            return response()->json([
                'message' => "Deposit recorded. {$student->first_name} {$student->last_name} enrolled as {$student->admission_number}.",
                'student' => $student,
                'deposit' => $deposit,
            ], 201);
        });
    }

    /**
     * Mirrors StudentApiController::store()'s student_number format and login-account
     * creation exactly, so applicants enrolled here behave identically to students
     * added the other way (same number format, same "student number as username /
     * national ID as default password" convention).
     */
    private function createStudentFromApplication(Application $application, array $depositData): Student
    {
        $attempts = 0;

        while (true) {
            $attempts++;
            $studentNumber = StudentNumberGenerator::generate($depositData['payment_date']);

            try {
                return DB::transaction(function () use ($application, $depositData, $studentNumber) {
                    $defaultPassword = $application->id_number ?: ('DGI@' . date('Y') . '!');

                    // users.email is globally unique — an applicant's email can already
                    // belong to an existing account (e.g. they're already a guardian of
                    // a sibling). Drop it rather than crash; login still works via
                    // student number.
                    $userEmail = $application->email;
                    if ($userEmail && DB::table('users')->where('email', $userEmail)->exists()) {
                        $userEmail = null;
                    }

                    $userId = DB::table('users')->insertGetId([
                        'name'                 => "{$application->first_name} {$application->last_name}",
                        'email'                => $userEmail,
                        'username'             => $studentNumber,
                        'password'             => Hash::make($defaultPassword),
                        'role'                 => 'student',
                        'is_active'            => true,
                        'must_change_password' => true,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                        'last_signed_in'       => now(),
                    ]);

                    $studentId = DB::table('students')->insertGetId([
                        'user_id'               => $userId,
                        'admission_number'      => $studentNumber,
                        'student_number'        => $studentNumber,
                        'national_id'           => $application->id_number,
                        'first_name'            => $application->first_name,
                        'last_name'             => $application->last_name,
                        'email'                 => $application->email,
                        'date_of_birth'         => $application->date_of_birth,
                        'class_id'              => null,
                        'form_id'               => $application->form_id,
                        'category_id'           => $application->category_id,
                        'academic_year_id'      => $application->academic_year_id,
                        'admission_date'        => $depositData['payment_date'],
                        'status'                => 'active',
                        'application_id'        => $application->id,
                        'document_verified_at'  => null,
                        'verification_due_at'   => $depositData['verification_due_at'],
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);

                    return Student::findOrFail($studentId);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                $isDuplicate = str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() === '23000';
                if ($attempts >= 2 || !$isDuplicate) {
                    throw $e;
                }
                // Another concurrent enrollment claimed this student/admission number — retry once with a fresh count.
            }
        }
    }

    /**
     * Mirrors StudentApiController::addGuardian()'s login-account creation (including
     * de-duplicating an existing guardian account by phone/username across siblings).
     */
    private function createGuardiansFromApplication(Application $application, Student $student): void
    {
        $slots = [
            ['name' => $application->guardian_name,  'email' => $application->guardian_email,  'phone' => $application->guardian_phone],
            ['name' => $application->guardian2_name, 'email' => $application->guardian2_email, 'phone' => $application->guardian2_phone],
            ['name' => $application->guardian3_name, 'email' => $application->guardian3_email, 'phone' => $application->guardian3_phone],
        ];

        $isFirst = true;
        foreach ($slots as $slot) {
            if (empty($slot['phone'])) {
                continue; // guardians.phone is required — skip incomplete secondary/tertiary slots rather than fail enrollment
            }

            $nameParts = explode(' ', trim((string) $slot['name']), 2);
            $firstName = $nameParts[0] !== '' ? $nameParts[0] : 'Guardian';
            $lastName  = $nameParts[1] ?? '-';

            $existingUser = DB::table('users')->where('username', $slot['phone'])->first();
            $userId = $existingUser->id ?? null;

            if (!$userId) {
                // A guardian's email can collide with the applicant's own (e.g. an adult
                // applicant listing themselves as guardian) or another existing account.
                // users.email is globally unique, so drop it rather than crash — the
                // guardian still logs in fine via phone/username.
                $email = $slot['email'] ?: null;
                if ($email && DB::table('users')->where('email', $email)->exists()) {
                    $email = null;
                }

                $userId = DB::table('users')->insertGetId([
                    'name'                 => "{$firstName} {$lastName}",
                    'email'                => $email,
                    'username'             => $slot['phone'],
                    'phone'                => $slot['phone'],
                    'password'             => Hash::make('DGI@' . date('Y') . '!'),
                    'role'                 => 'parent',
                    'is_active'            => true,
                    'must_change_password' => true,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                    'last_signed_in'       => now(),
                ]);
            }

            DB::table('guardians')->insert([
                'user_id'            => $userId,
                'student_id'         => $student->id,
                'first_name'         => $firstName,
                'last_name'          => $lastName,
                'email'              => $slot['email'] ?: null,
                'phone'              => $slot['phone'],
                'relationship'       => 'Parent/Guardian',
                'is_primary_contact' => $isFirst,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $isFirst = false;
        }
    }

    /**
     * Preview what's on file before staff commit to marking a student verified —
     * not a blind checkbox.
     */
    public function studentDocumentChecklist(Student $student)
    {
        return response()->json(['checklist' => $this->buildDocumentChecklist($student->application)]);
    }

    public function verifyStudentDocuments(Student $student)
    {
        $checklist = $this->buildDocumentChecklist($student->application);

        $student->update([
            'document_verified_at' => now(),
            'document_verified_by' => auth()->id(),
        ]);

        return response()->json([
            'message'   => 'Documents marked as verified.',
            'student'   => $student,
            'checklist' => $checklist,
        ]);
    }

    private function buildDocumentChecklist(?Application $application): array
    {
        $documentKeys = [
            'doc_student_id_path'      => 'Student ID / Birth Certificate',
            'doc_results_path'         => 'Results',
            'doc_parent_id_path'       => 'Parent ID / Birth',
            'doc_transfer_letter_path' => 'Transfer Letter',
        ];

        $checklist = [];
        foreach ($documentKeys as $key => $label) {
            $present = $application ? !empty($application->{$key}) : false;

            $latestRequest = $application
                ? DB::table('application_document_requests')
                    ->where('application_id', $application->id)
                    ->where('document_key', $key)
                    ->orderByDesc('id')
                    ->first()
                : null;

            $checklist[] = [
                'key'    => $key,
                'label'  => $label,
                'present' => $present,
                'status' => $latestRequest->status ?? ($present ? 'submitted' : 'missing'),
            ];
        }

        return $checklist;
    }
}
