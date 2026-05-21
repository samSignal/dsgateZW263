<?php

namespace App\Http\Controllers\Api\Admissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdmissionReviewController extends Controller
{
    private array $statuses = [
        'draft', 'submitted', 'under_review', 'interview_scheduled', 'documents_required',
        'accepted', 'rejected', 'waitlisted', 'enrollment_pending', 'enrolled',
    ];

    public function index(Request $request)
    {
        $query = DB::table('admission_applications as aa')
            ->leftJoin('forms', 'aa.applying_form_id', '=', 'forms.id')
            ->leftJoin('academic_years', 'aa.academic_year_id', '=', 'academic_years.id')
            ->select('aa.*', 'forms.name as form_name', 'academic_years.name as academic_year_name');

        if ($request->filled('status')) {
            $query->where('aa.status', $request->input('status'));
        }
        if ($request->filled('application_type')) {
            $query->where('aa.application_type', $request->input('application_type'));
        }
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('aa.student_first_name', 'like', $search)
                    ->orWhere('aa.student_last_name', 'like', $search)
                    ->orWhere('aa.application_number', 'like', $search)
                    ->orWhere('aa.tracking_token', 'like', $search)
                    ->orWhere('aa.guardian_name', 'like', $search);
            });
        }

        return response()->json($query->orderByDesc('aa.created_at')->paginate($request->integer('per_page', 20)));
    }

    public function show(int $id)
    {
        return $this->review($id);
    }

    public function review(int $id)
    {
        $app = DB::table('admission_applications as aa')
            ->leftJoin('forms', 'aa.applying_form_id', '=', 'forms.id')
            ->leftJoin('academic_years', 'aa.academic_year_id', '=', 'academic_years.id')
            ->leftJoin('terms', 'aa.term_id', '=', 'terms.id')
            ->select('aa.*', 'forms.name as form_name', 'academic_years.name as academic_year_name', 'terms.name as term_name')
            ->where('aa.id', $id)
            ->first();

        if (!$app) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        return response()->json([
            'application' => $app,
            'documents' => DB::table('application_documents')->where('application_id', $id)->orderBy('document_type')->get(),
            'notifications' => DB::table('application_notifications')->where('application_id', $id)->orderByDesc('created_at')->get(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', $this->statuses),
            'remarks' => 'nullable|string',
            'interview_at' => 'nullable|date',
        ]);

        return $this->setStatus($id, $request->input('status'), $request->input('remarks'), 'Application Status Updated');
    }

    public function requestDocuments(Request $request, int $id)
    {
        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'in:birth_certificate,passport_photo,grade7_report,latest_report,transfer_letter,discipline_record,medical_record',
            'remarks' => 'nullable|string',
        ]);

        $message = $request->input('remarks') ?: 'Please upload the following documents: ' . implode(', ', $request->input('documents'));
        return $this->setStatus($id, 'documents_required', $message, 'Documents Required');
    }

    public function accept(Request $request, int $id)
    {
        $request->validate([
            'remarks' => 'nullable|string',
            'assigned_stream_id' => 'nullable|integer|exists:streams,id',
        ]);

        DB::beginTransaction();
        try {
            $app = DB::table('admission_applications')->where('id', $id)->lockForUpdate()->first();
            if (!$app) {
                DB::rollBack();
                return response()->json(['message' => 'Application not found.'], 404);
            }

            $studentUserId = $app->student_user_id ?: $this->createUser(
                "{$app->student_first_name} {$app->student_last_name}",
                strtolower($app->application_number) . '@destinygate.ac.zw',
                'student',
                $app->birth_certificate_number
            );

            $studentId = $app->enrolled_student_id ?: DB::table('students')->insertGetId([
                'user_id' => $studentUserId,
                'admission_number' => $app->application_number,
                'first_name' => $app->student_first_name,
                'last_name' => $app->student_last_name,
                'date_of_birth' => $app->date_of_birth,
                'gender' => $app->gender,
                'admission_date' => now(),
                'status' => 'active',
                'medical_conditions' => $app->medical_information,
                'national_id' => $app->student_national_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $guardianUserId = $app->guardian_user_id ?: $this->createUser(
                $app->guardian_name,
                $app->guardian_email,
                'parent',
                $app->guardian_national_id,
                $app->guardian_phone
            );

            if (!DB::table('guardians')->where('student_id', $studentId)->where('email', $app->guardian_email)->exists()) {
                $names = preg_split('/\s+/', trim($app->guardian_name), 2);
                DB::table('guardians')->insert([
                    'user_id' => $guardianUserId,
                    'student_id' => $studentId,
                    'first_name' => $names[0] ?? $app->guardian_name,
                    'last_name' => $names[1] ?? '',
                    'email' => $app->guardian_email,
                    'phone' => $app->guardian_phone,
                    'relationship' => 'Guardian',
                    'address' => $app->address,
                    'occupation' => $app->occupation,
                    'is_primary_contact' => true,
                    'national_id' => $app->guardian_national_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('admission_applications')->where('id', $id)->update([
                'status' => 'accepted',
                'progress_percentage' => 80,
                'status_updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'acceptance_date' => now(),
                'enrolled_student_id' => $studentId,
                'guardian_user_id' => $guardianUserId,
                'student_user_id' => $studentUserId,
                'assigned_stream_id' => $request->input('assigned_stream_id'),
                'remarks' => $request->input('remarks'),
                'updated_at' => now(),
            ]);

            $this->notify($id, 'Congratulations! Application Accepted', 'Your application has been accepted. Enrollment instructions are now available in the tracking portal.', 'success');

            DB::commit();
            return response()->json(['message' => 'Application accepted and portal accounts created.', 'student_id' => $studentId]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Acceptance failed: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['remarks' => 'required|string']);
        return $this->setStatus($id, 'rejected', $request->input('remarks'), 'Application Not Successful');
    }

    public function enroll(Request $request, int $id)
    {
        $request->validate([
            'assigned_stream_id' => 'nullable|integer|exists:streams,id',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $app = DB::table('admission_applications')->where('id', $id)->lockForUpdate()->first();
            if (!$app) {
                DB::rollBack();
                return response()->json(['message' => 'Application not found.'], 404);
            }
            if (!$app->enrolled_student_id) {
                DB::rollBack();
                return response()->json(['message' => 'Accept the application before enrollment.'], 422);
            }

            $studentNumber = $this->generateStudentNumber();
            DB::table('students')->where('id', $app->enrolled_student_id)->update([
                'student_number' => $studentNumber,
                'class_id' => $request->input('class_id'),
                'updated_at' => now(),
            ]);

            // Create initial finance profile/bill if requested
            if ($app->academic_year_id && $app->term_id) {
                // Check if student_bills table exists and create an initial 'Admission Fee' bill if needed
                DB::table('student_bills')->insert([
                    'bill_number' => 'BILL-' . strtoupper(Str::random(8)),
                    'student_id' => $app->enrolled_student_id,
                    'academic_year_id' => $app->academic_year_id,
                    'term_id' => $app->term_id,
                    'fee_category_id' => 1, // Assuming 1 is a default category like 'Tuition' or 'Admission'
                    'description' => 'Admission and Enrollment Fee',
                    'amount' => 0, // Set to 0 for now, bursar can update
                    'amount_paid' => 0,
                    'balance' => 0,
                    'status' => 'unpaid',
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('admission_applications')->where('id', $id)->update([
                'status' => 'enrolled',
                'progress_percentage' => 100,
                'status_updated_at' => now(),
                'enrollment_completed_at' => now(),
                'assigned_stream_id' => $request->input('assigned_stream_id', $app->assigned_stream_id),
                'remarks' => $request->input('remarks', $app->remarks),
                'updated_at' => now(),
            ]);

            $this->notify($id, 'Enrollment Completed', 'Enrollment is complete. Welcome to DestinyGate Institute.', 'success');

            DB::commit();
            return response()->json(['message' => 'Enrollment completed.', 'student_number' => $studentNumber]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Enrollment failed: ' . $e->getMessage()], 500);
        }
    }

    private function setStatus(int $id, string $status, ?string $remarks, string $title)
    {
        DB::beginTransaction();
        try {
            if (!DB::table('admission_applications')->where('id', $id)->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'Application not found.'], 404);
            }

            DB::table('admission_applications')->where('id', $id)->update([
                'status' => $status,
                'progress_percentage' => $this->progressFor($status),
                'remarks' => $remarks,
                'status_updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            $this->notify($id, $title, $remarks ?: 'Your application status has been updated.', $status === 'rejected' ? 'rejected' : 'info');

            DB::commit();
            return response()->json(['message' => 'Application updated successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed.'], 500);
        }
    }

    private function createUser(string $name, string $email, string $role, string $passwordSeed, ?string $phone = null): int
    {
        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing) {
            return $existing->id;
        }

        return DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'username' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'phone' => $phone,
            'password' => Hash::make($passwordSeed),
            'role' => $role,
            'is_active' => true,
            'must_change_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateStudentNumber(): string
    {
        $year = date('Y');
        $count = DB::table('students')->whereYear('created_at', $year)->whereNotNull('student_number')->lockForUpdate()->count() + 1;
        $number = 'DGI-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (DB::table('students')->where('student_number', $number)->exists()) {
            $count++;
            $number = 'DGI-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }
        return $number;
    }

    private function progressFor(string $status): int
    {
        return [
            'submitted' => 10,
            'under_review' => 30,
            'interview_scheduled' => 50,
            'documents_required' => 55,
            'waitlisted' => 65,
            'accepted' => 80,
            'enrollment_pending' => 90,
            'enrolled' => 100,
            'rejected' => 100,
        ][$status] ?? 0;
    }

    private function notify(int $id, string $title, string $message, string $type): void
    {
        DB::table('application_notifications')->insert([
            'application_id' => $id,
            'title' => $title,
            'message' => $message,
            'notification_channel' => 'system',
            'notification_type' => $type,
            'sent_by' => Auth::id(),
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
