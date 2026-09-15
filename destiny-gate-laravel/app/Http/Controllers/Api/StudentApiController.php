<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Finance\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\BillGenerationService;
use App\Support\EnrollmentRoadmap;
use App\Support\StreamNativeEnrollmentEngine;
use App\Support\StudentNumberGenerator;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentApiController extends Controller
{
    use ExportsReports;

    /* ── class list (filterable, downloadable roster) ───────────────────────*/
    public function classList(Request $request)
    {
        // Class filtering goes through the same form/stream resolver used everywhere else
        // (StudentBillController::billingStatus, attendance sessions, etc.) so it correctly
        // includes both modern stream_id-linked and legacy class_id-linked students —
        // filtering on students.stream_id directly would silently miss the legacy ones.
        $ids = null;
        if ($request->stream_id) {
            $ids = StudentStreamResolver::studentIdsForStream((int) $request->stream_id, null, false);
        } elseif ($request->form_id) {
            $ids = StudentStreamResolver::studentIdsForForm((int) $request->form_id, null, false);
        }

        $query = DB::table('students')
            ->leftJoin('guardians', function ($j) {
                $j->on('guardians.student_id', '=', 'students.id')->where('guardians.is_primary_contact', true);
            })
            ->select('students.*', 'guardians.first_name as guardian_first_name', 'guardians.last_name as guardian_last_name', 'guardians.phone as guardian_phone');

        if ($ids !== null) $query->whereIn('students.id', $ids);
        $query->where('students.status', $request->filled('status') ? $request->status : 'active');
        if ($request->gender) $query->where('students.gender', $request->gender);
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('students.first_name', 'like', $s)
                  ->orWhere('students.last_name', 'like', $s)
                  ->orWhere('students.student_number', 'like', $s)
                  ->orWhere('students.admission_number', 'like', $s);
            });
        }

        $items = $query->orderBy('students.first_name')->get();
        $items = collect(StudentStreamResolver::attachResolvedFieldsToCollection($items))->map(function ($s) {
            $s->guardian_name = trim(($s->guardian_first_name ?? '') . ' ' . ($s->guardian_last_name ?? '')) ?: null;
            return $s;
        })->values();

        $formName = $request->form_id ? DB::table('forms')->where('id', $request->form_id)->value('name') : null;
        $streamName = $request->stream_id ? DB::table('streams')->where('id', $request->stream_id)->value('name') : null;
        $scopeLabel = trim(($formName ?? 'All Forms') . ($streamName ? " {$streamName}" : ''));

        if ($request->format === 'csv') {
            return $this->exportCsv('class-list.csv',
                ['Student #', 'Name', 'Gender', 'Date of Birth', 'Form', 'Class', 'Category', 'Guardian', 'Guardian Phone', 'Status'],
                $items->map(fn ($s) => [
                    $s->student_number ?? $s->admission_number,
                    trim("{$s->first_name} {$s->last_name}"),
                    $s->gender,
                    $s->date_of_birth,
                    $s->resolved_form_name,
                    $s->resolved_stream_name,
                    $s->resolved_category_name,
                    $s->guardian_name,
                    $s->guardian_phone,
                    $s->status,
                ])
            );
        }

        if ($request->format === 'pdf') {
            return $this->exportPdf('reports.students.class-list',
                ['students' => $items, 'scopeLabel' => $scopeLabel, 'statusLabel' => $request->filled('status') ? $request->status : 'active'],
                'class-list.pdf');
        }

        return response()->json($items);
    }

    /* ── index ────────────────────────────────────────────────────────────*/
    public function index(Request $request)
    {
        $query = DB::table('students')
            ->select('students.*');

        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('students.first_name',     'like', $s)
                  ->orWhere('students.last_name',     'like', $s)
                  ->orWhere('students.student_number','like', $s)
                  ->orWhere('students.admission_number','like', $s);
            });
        }

        if ($request->status)   $query->where('students.status',   $request->status);
        if ($request->class_id) $query->where('students.class_id', $request->class_id);

        $perPage = (int)($request->per_page ?? 20);
        $page    = (int)($request->page ?? 1);
        $total   = (clone $query)->count();
        // Counted across the whole filtered set, not just this page — the page-1 count
        // alone would silently under-report once pagination actually pages past it.
        $pendingVerification = (clone $query)->whereNull('students.document_verified_at')->count();
        $items   = $query->orderBy('students.first_name')
                         ->offset(($page - 1) * $perPage)
                         ->limit($perPage)
                         ->get();
        $items = StudentStreamResolver::attachResolvedFieldsToCollection($items);

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'pending_verification_count' => $pendingVerification,
        ]);
    }

    /* ── store ────────────────────────────────────────────────────────────*/
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email|unique:students,email',
            'date_of_birth'      => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'stream_id'          => 'nullable|exists:streams,id',
            'admission_date'     => 'required|date',
            'national_id'        => 'nullable|string|max:50',
            'blood_type'         => 'nullable|string|max:10',
            'allergies'          => 'nullable|string',
            'medical_conditions' => 'nullable|string',
        ]);

        // Class is authoritative — never trust client-sent form/category, derive from the class record.
        $stream = !empty($data['stream_id']) ? DB::table('streams')->find($data['stream_id']) : null;

        DB::beginTransaction();
        try {
            // Generate student number
            $studentNumber   = StudentNumberGenerator::generate($data['admission_date']);
            $admissionNumber = $studentNumber; // use same as admission number

            // Create user account automatically. users.email is globally unique — this
            // student's email can already belong to an existing account (e.g. they're also
            // a guardian of a sibling), which the 'unique:students,email' validation above
            // doesn't catch. Drop it rather than crash; login still works via student number.
            $defaultPassword = $data['national_id'] ?? 'DGI@' . date('Y') . '!';
            $userEmail = $data['email'] ?? null;
            if ($userEmail && DB::table('users')->where('email', $userEmail)->exists()) {
                $userEmail = null;
            }
            $userId = DB::table('users')->insertGetId([
                'name'                 => "{$data['first_name']} {$data['last_name']}",
                'email'                => $userEmail,
                'username'             => $studentNumber,   // login with student number
                'password'             => Hash::make($defaultPassword),
                'role'                 => 'student',
                'is_active'            => true,
                'must_change_password' => true,
                'created_at'           => now(),
                'updated_at'           => now(),
                'last_signed_in'       => now(),
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id'            => $userId,
                'admission_number'   => $admissionNumber,
                'student_number'     => $studentNumber,
                'national_id'        => $data['national_id'] ?? null,
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'],
                'email'              => $data['email'] ?? null,
                'date_of_birth'      => $data['date_of_birth'] ?? null,
                'gender'             => $data['gender'] ?? null,
                'stream_id'          => $stream->id ?? null,
                'form_id'            => $stream->form_id ?? null,
                'category_id'        => $stream->category_id ?? null,
                'admission_date'     => $data['admission_date'],
                'status'             => 'active',
                'blood_type'         => $data['blood_type'] ?? null,
                'allergies'          => $data['allergies'] ?? null,
                'medical_conditions' => $data['medical_conditions'] ?? null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::commit();

            // Bills this student against whatever fee structures apply to their form for the
            // current term — the only trigger a newly enrolled student has for getting billed.
            BillGenerationService::autoGenerateForStudent($studentId);
            self::recordCurrentTermEnrollment($studentId);

            $student = DB::table('students')
                ->select('students.*')
                ->where('students.id', $studentId)
                ->first();
            $student = StudentStreamResolver::attachResolvedFields($student);

            return response()->json([
                'message'        => 'Student enrolled successfully.',
                'student'        => $student,
                'student_number' => $studentNumber,
                'login_info'     => [
                    'username' => $studentNumber,
                    'password' => 'National ID (or DGI@' . date('Y') . '! if no National ID provided)',
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to enroll student: ' . $e->getMessage()], 500);
        }
    }

    /* ── bulk import (existing students, backfilled from before this system) ───*/

    /**
     * For students who already attend the school before it started using this system —
     * as opposed to store() above, which is for a brand-new admission going through this
     * system's own enrollment process for the first time. Deliberately does NOT call
     * BillGenerationService::autoGenerateForStudent() — the school already handled these
     * students' fees for whatever's been billed so far outside this system, and
     * auto-billing them on import risks double-charging a family. Bill them from the
     * Generate Bills screen once you're ready to start tracking their fees here.
     *
     * Each row is created in its own transaction so one bad row (a typo'd class name, a
     * duplicate student number) doesn't sink the rest of the batch — the response reports
     * success/failure per row rather than all-or-nothing.
     */
    public function bulkImport(Request $request)
    {
        $request->validate(['csv' => 'required|file|mimes:csv,txt']);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['message' => 'Could not read the uploaded file.'], 422);
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return response()->json(['message' => 'The CSV file is empty.'], 422);
        }
        $headers = array_map(fn ($h) => strtolower(trim(str_replace(' ', '_', $h))), $headerRow);

        $results = [];
        $created = 0;
        $failed = 0;
        $rowNum = 1;

        while (($cols = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count(array_filter($cols, fn ($v) => trim((string) $v) !== '')) === 0) continue; // skip blank rows

            // Real-world CSVs (hand-edited in a spreadsheet) often end up a column short or
            // long on a given row — pad or truncate to the header count instead of crashing
            // array_combine() over one ragged row and losing the whole batch.
            $row = array_combine($headers, array_slice(array_pad($cols, count($headers), null), 0, count($headers)));
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

            try {
                [$studentNumber, $warnings] = $this->importRow($row);
                $created++;
                $results[] = ['row' => $rowNum, 'name' => $name, 'status' => 'created', 'student_number' => $studentNumber, 'message' => $warnings ? implode(' ', $warnings) : null];
            } catch (\Throwable $e) {
                $failed++;
                $results[] = ['row' => $rowNum, 'name' => $name ?: '(unnamed)', 'status' => 'error', 'student_number' => null, 'message' => $e->getMessage()];
            }
        }

        fclose($handle);

        return response()->json([
            'message'  => "{$created} student(s) imported, {$failed} failed.",
            'created'  => $created,
            'failed'   => $failed,
            'results'  => $results,
        ]);
    }

    /** @return array{0: string, 1: string[]} [student_number, warnings] */
    private function importRow(array $row): array
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName  = trim((string) ($row['last_name'] ?? ''));
        if ($firstName === '' || $lastName === '') {
            throw new \RuntimeException('first_name and last_name are required.');
        }

        // Form drives the student number for a backfilled student (see
        // StudentNumberGenerator::generateForImport()) — no admission date needed.
        $formName = trim((string) ($row['form'] ?? ''));
        if ($formName === '') {
            throw new \RuntimeException('form is required (e.g. "Form 1") — it determines the student number.');
        }
        $form = DB::table('forms')->whereRaw('lower(name) = ?', [strtolower($formName)])->first();
        if (!$form) {
            throw new \RuntimeException("Form '{$formName}' not found.");
        }

        $warnings = [];

        $stream = null;
        $className = trim((string) ($row['class'] ?? $row['stream'] ?? ''));
        if ($className !== '') {
            $stream = DB::table('streams')->where('form_id', $form->id)->whereRaw('lower(name) = ?', [strtolower($className)])->first();
            if (!$stream) $warnings[] = "Class '{$className}' not found under form '{$formName}', student left unassigned to a class.";
        }

        // Category (e.g. "Sciences") is captured independently of class/stream — some
        // forms (like a freshly-imported Form 5/6) may not have streams set up yet at all,
        // and this shouldn't be lost just because there's nowhere to attach it via a class.
        $categoryId = null;
        $categoryName = trim((string) ($row['category'] ?? ''));
        if ($categoryName !== '') {
            $category = DB::table('categories')->whereRaw('lower(name) = ?', [strtolower($categoryName)])->first();
            if ($category) $categoryId = $category->id;
            else $warnings[] = "Category '{$categoryName}' not found, left blank.";
        }
        $categoryId = $categoryId ?? $stream->category_id ?? null;

        $genderRaw = strtolower(trim((string) ($row['gender'] ?? '')));
        $gender = in_array($genderRaw, ['male', 'female', 'other']) ? $genderRaw
            : (in_array($genderRaw, ['m']) ? 'male' : (in_array($genderRaw, ['f']) ? 'female' : null));
        if ($genderRaw !== '' && $gender === null) $warnings[] = "Gender '{$row['gender']}' not recognized, left blank.";

        $dobRaw = trim((string) ($row['date_of_birth'] ?? ''));
        $dob = null;
        if ($dobRaw !== '') {
            $dobTs = strtotime($dobRaw);
            if ($dobTs === false) $warnings[] = "date_of_birth '{$dobRaw}' is not a recognizable date, left blank.";
            else $dob = date('Y-m-d', $dobTs);
        }

        // admission_date is informational only now (kept for the record — it's no longer
        // what the student number is based on for an import); default to today if omitted.
        $admissionDateRaw = trim((string) ($row['admission_date'] ?? ''));
        $admissionDate = now()->toDateString();
        if ($admissionDateRaw !== '') {
            $admissionTs = strtotime(preg_match('/^\d{4}$/', $admissionDateRaw) ? "{$admissionDateRaw}-01-01" : $admissionDateRaw);
            if ($admissionTs === false) $warnings[] = "admission_date '{$admissionDateRaw}' is not a recognizable date, defaulted to today.";
            else $admissionDate = date('Y-m-d', $admissionTs);
        }

        // A prior balance owed / a payment already collected both need a term to attach
        // to — only require one if either money field is actually given.
        $prevBalance = (float) trim((string) ($row['previous_balance_owed'] ?? '0'));
        $paidNow     = (float) trim((string) ($row['amount_paid_now'] ?? '0'));
        $term = null;
        if ($prevBalance > 0 || $paidNow > 0) {
            $termName = trim((string) ($row['term'] ?? ''));
            if ($termName === '') throw new \RuntimeException('term is required when previous_balance_owed or amount_paid_now is given.');
            $term = DB::table('terms')->whereRaw('lower(name) = ?', [strtolower($termName)])->first();
            if (!$term) throw new \RuntimeException("Term '{$termName}' not found.");
        }

        $existingNumber = trim((string) ($row['student_number'] ?? $row['admission_number'] ?? ''));
        if ($existingNumber !== '') {
            $taken = DB::table('students')->where('student_number', $existingNumber)->orWhere('admission_number', $existingNumber)->exists();
            if ($taken) throw new \RuntimeException("Student number '{$existingNumber}' is already in use.");
            $studentNumber = $existingNumber;
        } else {
            $studentNumber = StudentNumberGenerator::generateForImport((int) $form->level);
        }

        $nationalId = trim((string) ($row['national_id'] ?? '')) ?: null;
        $emailRaw = trim((string) ($row['email'] ?? '')) ?: null;
        if ($emailRaw && !filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
            $warnings[] = "Email '{$emailRaw}' is not valid, left blank.";
            $emailRaw = null;
        }

        $bloodType  = trim((string) ($row['blood_type'] ?? '')) ?: null;
        $allergies  = trim((string) ($row['allergies'] ?? '')) ?: null;
        $medical    = trim((string) ($row['medical_conditions'] ?? '')) ?: null;

        // Guardian is entirely optional — leave every guardian_* column blank for a
        // student with no parent on file yet. But if a name is given, a phone number is
        // required (guardians.phone is NOT NULL and doubles as their login username), so
        // a name-with-no-phone doesn't silently create a broken account.
        $guardianFirstName = trim((string) ($row['guardian_first_name'] ?? ''));
        $guardianLastName  = trim((string) ($row['guardian_last_name'] ?? ''));
        $guardianPhone     = trim((string) ($row['guardian_phone'] ?? ''));
        $guardian = null;
        if ($guardianFirstName !== '' || $guardianLastName !== '') {
            if ($guardianPhone === '') {
                $warnings[] = 'Guardian name given but guardian_phone is missing (required for their login), so no guardian was added.';
            } else {
                $guardianEmail = trim((string) ($row['guardian_email'] ?? '')) ?: null;
                if ($guardianEmail && !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
                    $warnings[] = "Guardian email '{$guardianEmail}' is not valid, left blank.";
                    $guardianEmail = null;
                }
                $guardian = [
                    'first_name'   => $guardianFirstName,
                    'last_name'    => $guardianLastName,
                    'phone'        => $guardianPhone,
                    'email'        => $guardianEmail,
                    'national_id'  => trim((string) ($row['guardian_national_id'] ?? '')) ?: null,
                    'relationship' => trim((string) ($row['guardian_relationship'] ?? '')) ?: 'Guardian',
                    'address'      => trim((string) ($row['guardian_address'] ?? '')) ?: null,
                ];
            }
        }

        DB::transaction(function () use ($firstName, $lastName, $emailRaw, $dob, $gender, $form, $stream, $categoryId, $admissionDate, $nationalId, $studentNumber, $term, $prevBalance, $paidNow, $bloodType, $allergies, $medical, $guardian) {
            $defaultPassword = $nationalId ?? 'DGI@' . date('Y') . '!';
            $userEmail = $emailRaw;
            if ($userEmail && DB::table('users')->where('email', $userEmail)->exists()) {
                $userEmail = null;
            }
            // usernames must be unique too — an existing admission number could collide
            // with something already used as a login username elsewhere.
            if (DB::table('users')->where('username', $studentNumber)->exists()) {
                throw new \RuntimeException("Username '{$studentNumber}' is already taken by another account.");
            }

            $userId = DB::table('users')->insertGetId([
                'name'                 => "{$firstName} {$lastName}",
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
                'user_id'          => $userId,
                'admission_number' => $studentNumber,
                'student_number'   => $studentNumber,
                'national_id'      => $nationalId,
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                'email'            => $emailRaw,
                'date_of_birth'    => $dob,
                'gender'           => $gender,
                'stream_id'        => $stream->id ?? null,
                'form_id'          => $form->id,
                'category_id'      => $categoryId,
                'admission_date'   => $admissionDate,
                'status'           => 'active',
                'blood_type'       => $bloodType,
                'allergies'        => $allergies,
                'medical_conditions' => $medical,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Primary guardian, if given — mirrors StudentApiController::addGuardian()'s
            // account de-duplication so the same parent phone across siblings reuses one
            // login rather than erroring or creating a duplicate account.
            if ($guardian) {
                $existingGuardianUser = DB::table('users')->where('username', $guardian['phone'])->first();
                if ($existingGuardianUser) {
                    $guardianUserId = $existingGuardianUser->id;
                } else {
                    $guardianUserId = DB::table('users')->insertGetId([
                        'name'                 => "{$guardian['first_name']} {$guardian['last_name']}",
                        'email'                => $guardian['email'],
                        'username'             => $guardian['phone'],
                        'phone'                => $guardian['phone'],
                        'password'             => Hash::make($guardian['national_id'] ?? 'DGI@' . date('Y') . '!'),
                        'role'                 => 'parent',
                        'is_active'            => true,
                        'must_change_password' => true,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                        'last_signed_in'       => now(),
                    ]);
                }

                DB::table('guardians')->insertGetId([
                    'user_id'            => $guardianUserId,
                    'student_id'         => $studentId,
                    'first_name'         => $guardian['first_name'],
                    'last_name'          => $guardian['last_name'],
                    'email'              => $guardian['email'],
                    'phone'              => $guardian['phone'],
                    'national_id'        => $guardian['national_id'],
                    'relationship'       => $guardian['relationship'],
                    'address'            => $guardian['address'],
                    'is_primary_contact' => true,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            self::recordCurrentTermEnrollment($studentId);

            // A balance carried over from before this system existed — recorded as a real
            // bill (not just a ledger tweak) so it shows up consistently everywhere else in
            // Finance that reads student_bills, exactly like every other charge does.
            if ($prevBalance > 0) {
                BillGenerationService::createBill(
                    $studentId, (int) $term->academic_year_id, (int) $term->id,
                    self::openingBalanceFeeCategoryId(),
                    'Opening balance carried over from before this system',
                    $prevBalance, null, null
                );
            }

            // Money the school already collected before/during this import — recorded as a
            // real payment (reusing PaymentController::store() itself) so it allocates
            // against the opening balance above the same way any other payment would, and
            // any excess correctly becomes account credit rather than being lost.
            if ($paidNow > 0) {
                $paymentRequest = Request::create('/api/finance/payments', 'POST', [
                    'student_id'       => $studentId,
                    'academic_year_id' => $term->academic_year_id,
                    'term_id'          => $term->id,
                    'amount'           => $paidNow,
                    'payment_method'   => 'other',
                    'payment_date'     => now()->toDateString(),
                    'notes'            => 'Recorded via bulk student import — fees collected before this system was in use.',
                ]);
                $paymentResponse = app(\App\Http\Controllers\Api\Finance\PaymentController::class)->store($paymentRequest);
                if ($paymentResponse->getStatusCode() >= 300) {
                    $body = json_decode($paymentResponse->getContent(), true);
                    throw new \RuntimeException('Could not record prior payment: ' . ($body['message'] ?? 'unknown error'));
                }
            }
        });

        return [$studentNumber, $warnings];
    }

    private static function openingBalanceFeeCategoryId(): int
    {
        return DB::table('fee_categories')->where('code', 'OPEN-BAL')->value('id')
            ?? DB::table('fee_categories')->insertGetId([
                'code'        => 'OPEN-BAL',
                'name'        => 'Opening Balance (Pre-System Arrears)',
                'description' => 'Balance carried forward from before the school started using this system. Created automatically by the student bulk-import tool.',
                'frequency'   => 'once_off',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
    }

    /**
     * Records this student's placement for the current term in student_enrollments — the
     * one real per-term history log the Enrollment History card reads (see
     * EnrollmentRoadmap for the separate, purely computed graduation-track map). Silently
     * no-ops for a student with no stream yet (e.g. an unmapped Form 5/6 import before
     * classes exist for that form) rather than failing enrollment/import over it.
     */
    private static function recordCurrentTermEnrollment(int $studentId): void
    {
        $term = DB::table('terms')->where('is_current', true)->first();
        if (!$term) return;
        StreamNativeEnrollmentEngine::ensureEnrollment($studentId, (int) $term->academic_year_id, (int) $term->id, auth()->id());
    }

    /* ── enrollment history / graduation roadmap ─────────────────────────────*/
    public function enrollmentHistory(int $id)
    {
        abort_if(!DB::table('students')->where('id', $id)->exists(), 404, 'Student not found.');

        $history = DB::table('student_enrollments as se')
            ->join('academic_years as ay', 'se.academic_year_id', '=', 'ay.id')
            ->join('terms as t', 'se.term_id', '=', 't.id')
            ->join('forms as f', 'se.form_id', '=', 'f.id')
            ->join('streams as st', 'se.stream_id', '=', 'st.id')
            ->select('se.*', 'ay.name as academic_year_name', 't.name as term_name', 'f.name as form_name', 'st.name as stream_name')
            ->where('se.student_id', $id)
            ->orderBy('ay.start_date')
            ->orderBy('t.start_date')
            ->get();

        return response()->json([
            'roadmap' => EnrollmentRoadmap::forStudent($id),
            'history' => $history,
        ]);
    }

    /* ── show ─────────────────────────────────────────────────────────────*/
    public function show(int $id)
    {
        $student = DB::table('students')
            ->select('students.*')
            ->where('students.id', $id)
            ->first();

        abort_if(!$student, 404, 'Student not found.');
        $student = StudentStreamResolver::attachResolvedFields($student);

        $guardians = DB::table('guardians')->where('student_id', $id)->get();
        $fees      = DB::table('student_fees')->where('student_id', $id)->get();
        $progress  = DB::table('academic_progress')
            ->join('subjects', 'academic_progress.subject_id', '=', 'subjects.id')
            ->select('academic_progress.*', 'subjects.name as subject_name', 'subjects.code as subject_code')
            ->where('academic_progress.student_id', $id)
            ->get();
        $attendance = DB::table('attendance')->where('student_id', $id)->orderByDesc('date')->limit(30)->get();
        $behaviour  = DB::table('behaviour_records')->where('student_id', $id)->orderByDesc('issue_date')->get();

        $applicationDocuments = null;
        if ($student->application_id) {
            $applicationDocuments = DB::table('applications')
                ->select('application_number', 'doc_student_id_path', 'doc_results_path', 'doc_parent_id_path', 'doc_transfer_letter_path')
                ->where('id', $student->application_id)
                ->first();
        }

        return response()->json([
            ...(array) $student,
            'guardians'             => $guardians,
            'fees'                  => $fees,
            'academic_progress'     => $progress,
            'attendance'            => $attendance,
            'behaviour_records'     => $behaviour,
            'application_documents' => $applicationDocuments,
        ]);
    }

    /* ── update ───────────────────────────────────────────────────────────*/
    public function update(Request $request, int $id)
    {
        $student = DB::table('students')->find($id);
        abort_if(!$student, 404, 'Student not found.');

        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email|unique:students,email,' . $id,
            'date_of_birth'      => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'stream_id'          => 'nullable|exists:streams,id',
            'status'             => 'required|in:active,inactive,transferred,graduated,suspended,deceased',
            'blood_type'         => 'nullable|string|max:10',
            'allergies'          => 'nullable|string',
            'medical_conditions' => 'nullable|string',
        ]);

        // Only touch class assignment when the caller actually sent stream_id — class is
        // authoritative for form/category, never trust client-sent values for those.
        if ($request->has('stream_id')) {
            $stream = !empty($data['stream_id']) ? DB::table('streams')->find($data['stream_id']) : null;
            $data['form_id']     = $stream->form_id ?? null;
            $data['category_id'] = $stream->category_id ?? null;
        }

        DB::table('students')->where('id', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        // Sync user name
        if ($student->user_id) {
            DB::table('users')->where('id', $student->user_id)->update([
                'name'       => "{$data['first_name']} {$data['last_name']}",
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Student updated.',
            'student' => StudentStreamResolver::attachResolvedFields(DB::table('students')->where('id', $id)->first()),
        ]);
    }

    /* ── addGuardian ──────────────────────────────────────────────────────*/
    public function addGuardian(Request $request, int $studentId)
    {
        $student = DB::table('students')->find($studentId);
        abort_if(!$student, 404, 'Student not found.');

        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'nullable|email',
            'phone'              => 'required|string|max:20',
            'national_id'        => 'nullable|string|max:50',
            'relationship'       => 'required|string|max:50',
            'address'            => 'nullable|string',
            'city'               => 'nullable|string|max:100',
            'country'            => 'nullable|string|max:100',
            'occupation'         => 'nullable|string|max:100',
            'is_primary_contact' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // If primary contact, unset others
            if (!empty($data['is_primary_contact'])) {
                DB::table('guardians')->where('student_id', $studentId)->update(['is_primary_contact' => false]);
            }

            // Create user account for guardian using phone as username
            $defaultPassword = $data['national_id'] ?? 'DGI@' . date('Y') . '!';

            // Check if user with this phone/username already exists
            $existingUser = DB::table('users')->where('username', $data['phone'])->first();
            $userId = null;

            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                $userId = DB::table('users')->insertGetId([
                    'name'                 => "{$data['first_name']} {$data['last_name']}",
                    'email'                => $data['email'] ?? null,
                    'username'             => $data['phone'],   // login with phone number
                    'phone'                => $data['phone'],
                    'password'             => Hash::make($defaultPassword),
                    'role'                 => 'parent',
                    'is_active'            => true,
                    'must_change_password' => true,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                    'last_signed_in'       => now(),
                ]);
            }

            $guardianId = DB::table('guardians')->insertGetId([
                'user_id'            => $userId,
                'student_id'         => $studentId,
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'],
                'email'              => $data['email'] ?? null,
                'phone'              => $data['phone'],
                'national_id'        => $data['national_id'] ?? null,
                'relationship'       => $data['relationship'],
                'address'            => $data['address'] ?? null,
                'city'               => $data['city'] ?? null,
                'country'            => $data['country'] ?? null,
                'occupation'         => $data['occupation'] ?? null,
                'is_primary_contact' => $data['is_primary_contact'] ?? false,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Guardian added successfully.',
                'guardian'   => DB::table('guardians')->find($guardianId),
                'login_info' => [
                    'username' => $data['phone'],
                    'password' => 'National ID (or DGI@' . date('Y') . '! if no National ID provided)',
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add guardian: ' . $e->getMessage()], 500);
        }
    }

    /* ── helpers ──────────────────────────────────────────────────────────*/
    public function classes()
    {
        return response()->json(
            DB::table('classes')->orderBy('class_name')->get()
        );
    }

    public function subjects()
    {
        return response()->json(
            DB::table('subjects')->orderBy('name')->get()
        );
    }
}
