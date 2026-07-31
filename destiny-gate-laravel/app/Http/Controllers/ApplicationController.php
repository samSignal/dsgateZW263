<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function create(Request $request)
    {
        $academicYears = DB::table('academic_years')->orderByDesc('is_active')->orderByDesc('start_date')->get();
        $terms = DB::table('terms')->orderByDesc('is_current')->orderBy('name')->get();
        $forms = DB::table('forms')->orderBy('level')->get();
        $categories = DB::table('categories')->where('is_active', true)->orderBy('name')->get();

        $mode = (string) $request->get('mode', 'apply');
        $resumeToken = trim((string) $request->query('token', ''));
        $draft = null;

        if ($resumeToken !== '') {
            $draft = Application::where('resume_token', $resumeToken)->where('is_draft', true)->first();
        }

        return view('applications.create', compact('academicYears', 'terms', 'forms', 'categories', 'mode', 'resumeToken', 'draft'));
    }

    public function resumeForm(Request $request)
    {
        $request->merge(['mode' => 'resume']);
        return $this->create($request);
    }

    public function resume(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:80',
        ]);

        return redirect()->route('applications.create', ['token' => $data['token']]);
    }

    public function trackForm(Request $request)
    {
        $request->merge(['mode' => 'track']);
        $autoTrack = (bool) session()->pull('auto_track', false);
        $applicationNumber = $request->old('application_number');
        $email = $request->old('email');

        if ($autoTrack && $applicationNumber && $email) {
            $application = Application::where('application_number', $applicationNumber)
                ->where('email', $email)
                ->first();

            $view = $this->create($request);
            $requests = $application
                ? DB::table('application_document_requests')
                    ->where('application_id', $application->id)
                    ->orderByDesc('id')
                    ->get()
                : collect();

            $documentLabels = [
                'doc_student_id_path' => 'Student Birth Certificate / National ID',
                'doc_results_path' => 'Previous / Current Results',
                'doc_parent_id_path' => 'Parent ID / Birth Certificate',
                'doc_transfer_letter_path' => 'Transfer Letter',
            ];

            $pendingRequests = collect($requests)->where('status', 'pending')->values();

            return $view
                ->with('trackedApplication', $application)
                ->with('trackedRequests', $requests)
                ->with('pendingRequests', $pendingRequests)
                ->with('documentLabels', $documentLabels);
        }

        return $this->create($request);
    }

    public function track(Request $request)
    {
        $data = $request->validate([
            'application_number' => 'required|string|max:50',
            'email' => 'required|email',
        ]);

        $request->flash();

        $application = Application::where('application_number', $data['application_number'])
            ->where('email', $data['email'])
            ->first();

        $request->merge(['mode' => 'track']);
        $view = $this->create($request);
        $requests = $application
            ? DB::table('application_document_requests')
                ->where('application_id', $application->id)
                ->orderByDesc('id')
                ->get()
            : collect();

        $documentLabels = [
            'doc_student_id_path' => 'Student Birth Certificate / National ID',
            'doc_results_path' => 'Previous / Current Results',
            'doc_parent_id_path' => 'Parent ID / Birth Certificate',
            'doc_transfer_letter_path' => 'Transfer Letter',
        ];

        $pendingRequests = collect($requests)->where('status', 'pending')->values();

        return $view
            ->with('trackedApplication', $application)
            ->with('trackedRequests', $requests)
            ->with('pendingRequests', $pendingRequests)
            ->with('documentLabels', $documentLabels);
    }

    public function uploadRequestedDocument(Request $request, int $requestId)
    {
        $data = $request->validate([
            'application_number' => 'required|string|max:50',
            'email' => 'required|email',
            'document' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $docRequest = DB::table('application_document_requests')->where('id', $requestId)->first();
        abort_if(!$docRequest, 404);

        $application = Application::where('id', $docRequest->application_id)
            ->where('application_number', $data['application_number'])
            ->where('email', $data['email'])
            ->first();

        if (!$application) {
            return redirect()->route('applications.track')
                ->withInput([
                    'application_number' => $data['application_number'],
                    'email' => $data['email'],
                ])
                ->with('error', 'Application not found for those details.');
        }

        if ($docRequest->status !== 'pending') {
            return redirect()->route('applications.track')
                ->withInput([
                    'application_number' => $data['application_number'],
                    'email' => $data['email'],
                ])
                ->with('error', 'This request is no longer pending.');
        }

        $allowed = ['doc_student_id_path', 'doc_results_path', 'doc_parent_id_path', 'doc_transfer_letter_path'];
        if (!in_array($docRequest->document_key, $allowed, true)) {
            abort(422);
        }

        $filesBasePath = 'applications/' . $application->application_number;
        $newPath = $request->file('document')->store($filesBasePath, 'public');

        $oldPath = $application->{$docRequest->document_key} ?? null;
        $application->{$docRequest->document_key} = $newPath;
        $application->save();

        DB::table('application_document_requests')->where('id', $docRequest->id)->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
            'old_path' => $docRequest->old_path ?? $oldPath,
            'new_path' => $newPath,
            'updated_at' => now(),
        ]);

        return redirect()->route('applications.track')
            ->withInput([
                'application_number' => $data['application_number'],
                'email' => $data['email'],
            ])
            ->with('auto_track', true)
            ->with('success', 'Document uploaded successfully. Please track your application for updates.');
    }

    public function offerLetter(string $token)
    {
        $token = trim($token);
        abort_if($token === '', 404);

        $application = Application::where('offer_letter_token', $token)
            ->whereIn('status', ['offered', 'enrolled'])
            ->first();

        abort_if(!$application, 404);

        $studentName = trim(collect([$application->first_name, $application->middle_name, $application->last_name])->filter()->join(' '));

        $offer = [
            'id' => $application->application_number,
            'version' => (string) ($application->offer_letter_version ?? 1),
            'expires_at' => $application->offer_letter_expires_at ? $application->offer_letter_expires_at->toDateString() : now()->addDays(21)->toDateString(),
            'accepted_at' => $application->offer_accepted_at ? $application->offer_accepted_at->toDateTimeString() : null,
        ];

        $applicationData = [
            'application_number' => $application->application_number,
            'student_name' => $studentName !== '' ? $studentName : '—',
            'date_of_birth' => $application->date_of_birth ? $application->date_of_birth->toDateString() : '—',
            'guardian_email' => $application->guardian_email ?? $application->email,
        ];

        $intake = [
            'academic_year' => $application->academic_year ?? '—',
            'form' => $application->intended_class ?? '—',
            'category' => (string) ($application->category_id ?? '—'),
        ];

        return view('admissions.offer-letter', [
            'token' => $token,
            'watermark' => strtoupper((string) $application->status),
            'offer' => $offer,
            'application' => $applicationData,
            'intake' => $intake,
        ]);
    }

    public function acceptOfferLetter(Request $request, string $token)
    {
        $token = trim($token);
        abort_if($token === '', 404);

        $application = Application::where('offer_letter_token', $token)
            ->whereIn('status', ['offered', 'enrolled'])
            ->first();

        abort_if(!$application, 404);

        if ($application->offer_letter_expires_at && $application->offer_letter_expires_at->isPast()) {
            return redirect()->route('applications.offer-letter', $token)
                ->with('error', 'This offer letter has expired. Please contact the admissions office.');
        }

        if (!$application->offer_accepted_at) {
            $application->offer_accepted_at = now();
            $application->save();
        }

        return redirect()->route('applications.offer-letter', $token)
            ->with('success', 'Offer letter accepted successfully. The admissions office can now prepare for your enrolment.');
    }

    public function sendOfferLetter(Request $request, string $token)
    {
        $data = $request->validate([
            'application_number' => 'required|string|max:50',
            'email' => 'required|email',
        ]);

        $token = trim($token);
        abort_if($token === '', 404);

        $application = Application::where('offer_letter_token', $token)
            ->whereIn('status', ['offered', 'enrolled'])
            ->where('application_number', $data['application_number'])
            ->where('email', $data['email'])
            ->first();

        if (!$application) {
            return redirect()->route('applications.track')
                ->withInput($data)
                ->with('auto_track', true)
                ->with('error', 'Application not found for those details.');
        }

        $offerLink = route('applications.offer-letter', $application->offer_letter_token);
        $studentName = trim(collect([$application->first_name, $application->middle_name, $application->last_name])->filter()->join(' '));
        $studentName = $studentName !== '' ? $studentName : 'Student';

        $text = implode("\n\n", [
            "Dear {$studentName},",
            "Congratulations. A provisional place has been offered for application {$application->application_number}.",
            "View your offer letter here: {$offerLink}",
            "If you have questions, please contact the Admissions Office.",
        ]);

        try {
            Mail::raw($text, function ($message) use ($application) {
                $message->to($application->email)
                    ->subject('Offer Letter - ' . $application->application_number);
            });
        } catch (\Throwable $e) {
            return redirect()->route('applications.track')
                ->withInput($data)
                ->with('auto_track', true)
                ->with('error', 'Unable to send the offer letter email right now. Please try again later.');
        }

        return redirect()->route('applications.track')
            ->withInput($data)
            ->with('auto_track', true)
            ->with('success', 'Offer letter sent to ' . $application->email . '.');
    }

    public function draft(Request $request)
    {
        $validated = $request->validate([
            'resume_token' => 'nullable|string|max:80',
            'current_step' => 'nullable|integer|min:1|max:5',

            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'term_id' => 'nullable|integer|exists:terms,id',
            'form_id' => 'nullable|integer|exists:forms,id',
            'category_id' => 'nullable|integer|exists:categories,id',

            'first_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'id_number' => 'nullable|string|max:50',
            'student_address' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',

            'guardian_name' => 'nullable|string|max:100',
            'guardian_email' => 'nullable|email',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian2_name' => 'nullable|string|max:100',
            'guardian2_email' => 'nullable|email',
            'guardian2_phone' => 'nullable|string|max:20',
            'guardian3_name' => 'nullable|string|max:100',
            'guardian3_email' => 'nullable|email',
            'guardian3_phone' => 'nullable|string|max:20',

            'previous_school' => 'nullable|string|max:150',
            'former_grade' => 'nullable|string|max:50',
            'reason_for_joining' => 'nullable|string|max:2000',

            'student_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'results_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'parent_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'transfer_letter' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $guardian2Provided = $request->filled('guardian2_name') || $request->filled('guardian2_email') || $request->filled('guardian2_phone');
        if ($guardian2Provided && !($request->filled('guardian2_name') && $request->filled('guardian2_email') && $request->filled('guardian2_phone'))) {
            throw ValidationException::withMessages([
                'guardian2_name' => 'Guardian 2 details must be completed (name, email, phone).',
            ]);
        }

        $guardian3Provided = $request->filled('guardian3_name') || $request->filled('guardian3_email') || $request->filled('guardian3_phone');
        if ($guardian3Provided && !($request->filled('guardian3_name') && $request->filled('guardian3_email') && $request->filled('guardian3_phone'))) {
            throw ValidationException::withMessages([
                'guardian3_name' => 'Guardian 3 details must be completed (name, email, phone).',
            ]);
        }

        $resumeToken = $validated['resume_token'] ?? null;
        $application = $resumeToken ? Application::where('resume_token', $resumeToken)->where('is_draft', true)->first() : null;

        if (!$application) {
            $resumeToken = $resumeToken ?: strtoupper(Str::random(12));
            while (Application::where('resume_token', $resumeToken)->exists()) {
                $resumeToken = strtoupper(Str::random(12));
            }

            $year = isset($validated['academic_year_id']) ? DB::table('academic_years')->find($validated['academic_year_id']) : null;
            $startYear = $year?->start_date ? substr((string) $year->start_date, 0, 4) : null;
            $fallbackYear = $year && preg_match('/\d{4}/', (string) ($year->name ?? ''), $m) ? $m[0] : date('Y');
            $appYear = $startYear ?: $fallbackYear;

            $applicationNumber = 'APP-' . $appYear . '-' . str_pad(Application::count() + 1, 4, '0', STR_PAD_LEFT);

            $application = new Application([
                'application_number' => $applicationNumber,
                'resume_token' => $resumeToken,
                'is_draft' => true,
            ]);
        }

        $application->academic_year_id = $validated['academic_year_id'] ?? $application->academic_year_id;
        $application->term_id = $validated['term_id'] ?? $application->term_id;
        $application->form_id = $validated['form_id'] ?? $application->form_id;
        $application->category_id = $validated['category_id'] ?? $application->category_id;

        foreach ([
            'first_name', 'middle_name', 'last_name', 'email', 'phone',
            'date_of_birth', 'id_number', 'student_address',
            'guardian_name', 'guardian_email', 'guardian_phone',
            'guardian2_name', 'guardian2_email', 'guardian2_phone',
            'guardian3_name', 'guardian3_email', 'guardian3_phone',
            'previous_school', 'former_grade', 'reason_for_joining',
        ] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $application->{$field} = $validated[$field];
            }
        }

        $application->last_saved_step = isset($validated['current_step']) ? (int) $validated['current_step'] : ($application->last_saved_step ?: 1);
        $application->is_draft = true;
        $application->resume_token = $resumeToken;

        $filesBasePath = 'applications/' . $application->application_number;
        if ($request->file('student_document')) $application->doc_student_id_path = $request->file('student_document')->store($filesBasePath, 'public');
        if ($request->file('results_document')) $application->doc_results_path = $request->file('results_document')->store($filesBasePath, 'public');
        if ($request->file('parent_document')) $application->doc_parent_id_path = $request->file('parent_document')->store($filesBasePath, 'public');
        if ($request->file('transfer_letter')) $application->doc_transfer_letter_path = $request->file('transfer_letter')->store($filesBasePath, 'public');

        $application->save();

        return redirect()->route('applications.create', ['token' => $application->resume_token])
            ->with('draft_saved', true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'resume_token' => 'nullable|string|max:80',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'term_id' => 'required|integer|exists:terms,id',
            'form_id' => 'required|integer|exists:forms,id',
            'category_id' => 'required|integer|exists:categories,id',

            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'id_number' => 'required|string|max:50',
            'student_address' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',

            'guardian_name' => 'required|string|max:100',
            'guardian_email' => 'required|email',
            'guardian_phone' => 'required|string|max:20',
            'guardian2_name' => 'nullable|string|max:100',
            'guardian2_email' => 'nullable|email',
            'guardian2_phone' => 'nullable|string|max:20',
            'guardian3_name' => 'nullable|string|max:100',
            'guardian3_email' => 'nullable|email',
            'guardian3_phone' => 'nullable|string|max:20',

            'previous_school' => 'required|string|max:150',
            'former_grade' => 'required|string|max:50',
            'reason_for_joining' => 'required|string|max:2000',

            'student_document' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'results_document' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'parent_document' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'transfer_letter' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $guardian2Provided = $request->filled('guardian2_name') || $request->filled('guardian2_email') || $request->filled('guardian2_phone');
        if ($guardian2Provided && !($request->filled('guardian2_name') && $request->filled('guardian2_email') && $request->filled('guardian2_phone'))) {
            throw ValidationException::withMessages([
                'guardian2_name' => 'Guardian 2 details must be completed (name, email, phone).',
            ]);
        }

        $guardian3Provided = $request->filled('guardian3_name') || $request->filled('guardian3_email') || $request->filled('guardian3_phone');
        if ($guardian3Provided && !($request->filled('guardian3_name') && $request->filled('guardian3_email') && $request->filled('guardian3_phone'))) {
            throw ValidationException::withMessages([
                'guardian3_name' => 'Guardian 3 details must be completed (name, email, phone).',
            ]);
        }

        $year = DB::table('academic_years')->find($validated['academic_year_id']);
        $term = DB::table('terms')->find($validated['term_id']);
        $form = DB::table('forms')->find($validated['form_id']);
        $category = DB::table('categories')->find($validated['category_id']);

        if (!$term || (int) $term->academic_year_id !== (int) $year->id) {
            throw ValidationException::withMessages([
                'term_id' => 'Selected term does not match the selected academic year.',
            ]);
        }

        $startYear = $year->start_date ? substr((string) $year->start_date, 0, 4) : null;
        $fallbackYear = preg_match('/\d{4}/', (string) ($year->name ?? ''), $m) ? $m[0] : date('Y');
        $appYear = $startYear ?: $fallbackYear;

        $draft = null;
        if (!empty($validated['resume_token'])) {
            $draft = Application::where('resume_token', $validated['resume_token'])->where('is_draft', true)->first();
        }

        $applicationNumber = $draft?->application_number ?: ('APP-' . $appYear . '-' . str_pad(Application::count() + 1, 4, '0', STR_PAD_LEFT));
        $filesBasePath = 'applications/' . $applicationNumber;

        $studentDocPath = $request->file('student_document')->store($filesBasePath, 'public');
        $resultsDocPath = $request->file('results_document')->store($filesBasePath, 'public');
        $parentDocPath = $request->file('parent_document')->store($filesBasePath, 'public');
        $transferDocPath = $request->file('transfer_letter') ? $request->file('transfer_letter')->store($filesBasePath, 'public') : null;

        $data = [
            'application_number' => $applicationNumber,
            'academic_year_id' => $validated['academic_year_id'],
            'term_id' => $validated['term_id'],
            'form_id' => $validated['form_id'],
            'category_id' => $validated['category_id'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'id_number' => $validated['id_number'],
            'student_address' => $validated['student_address'],
            'guardian_name' => $validated['guardian_name'],
            'guardian_email' => $validated['guardian_email'],
            'guardian_phone' => $validated['guardian_phone'],
            'guardian2_name' => $validated['guardian2_name'] ?? null,
            'guardian2_email' => $validated['guardian2_email'] ?? null,
            'guardian2_phone' => $validated['guardian2_phone'] ?? null,
            'guardian3_name' => $validated['guardian3_name'] ?? null,
            'guardian3_email' => $validated['guardian3_email'] ?? null,
            'guardian3_phone' => $validated['guardian3_phone'] ?? null,
            'previous_school' => $validated['previous_school'],
            'former_grade' => $validated['former_grade'],
            'reason_for_joining' => $validated['reason_for_joining'],
            'doc_student_id_path' => $studentDocPath,
            'doc_results_path' => $resultsDocPath,
            'doc_parent_id_path' => $parentDocPath,
            'doc_transfer_letter_path' => $transferDocPath,
            'academic_year' => $year->name,
            'intended_class' => trim(implode(' | ', array_filter([$form->name ?? null, $term->name ?? null, $category->name ?? null]))),
            'is_draft' => false,
            'last_saved_step' => null,
        ];

        if ($draft) {
            $draft->fill($data);
            $draft->is_draft = false;
            $draft->save();
            $application = $draft;
        } else {
            $application = Application::create($data);
        }

        return redirect()->route('applications.success', $application)
            ->with('success', 'Application submitted successfully. Your application number is ' . $application->application_number);
    }

    public function success(Application $application)
    {
        return view('applications.success', compact('application'));
    }
}
