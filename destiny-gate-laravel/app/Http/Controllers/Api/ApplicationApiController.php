<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationApiController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'academic_year_id' => 'required|integer|exists:academic_years,id',
                'term_id' => 'required|integer|exists:terms,id',
                'form_id' => 'required|integer|exists:forms,id',
                'category_id' => 'required|integer|exists:categories,id',

                'first_name' => 'required|string|max:100',
                'middle_name' => 'nullable|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'id_number' => 'required|string|max:50',
                'student_address' => 'required|string|max:255',

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

            $applicationNumber = 'APP-' . $appYear . '-' . str_pad(Application::count() + 1, 4, '0', STR_PAD_LEFT);
            $filesBasePath = 'applications/' . $applicationNumber;

            $studentDocPath = $request->file('student_document')->store($filesBasePath, 'public');
            $resultsDocPath = $request->file('results_document')->store($filesBasePath, 'public');
            $parentDocPath = $request->file('parent_document')->store($filesBasePath, 'public');
            $transferDocPath = $request->file('transfer_letter') ? $request->file('transfer_letter')->store($filesBasePath, 'public') : null;

            $data = [
                'application_number' => $applicationNumber,
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
            ];

            $app = Application::create($data);
            return response()->json(['message' => 'Application submitted.', 'application' => $app], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Application store error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to submit application.', 'details' => $e->getMessage()], 500);
        }
    }
}
