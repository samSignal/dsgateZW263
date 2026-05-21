<?php

namespace App\Http\Controllers\Api\AdmissionsManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdmissionApplicantController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('admission_applications');

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('student_first_name', 'like', "%{$search}%")
                  ->orWhere('student_last_name', 'like', "%{$search}%")
                  ->orWhere('guardian_phone', 'like', "%{$search}%")
                  ->orWhere('tracking_token', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('application_type')) {
            $query->where('application_type', $request->input('application_type'));
        }
        if ($request->filled('applying_form_id')) {
            $query->where('applying_form_id', $request->input('applying_form_id'));
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }
        if ($request->filled('submitted_date')) {
            $query->whereDate('submitted_at', $request->input('submitted_date'));
        }

        $perPage = $request->input('per_page', 15);
        $applications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $applications
        ]);
    }

    public function show($id)
    {
        $application = DB::table('admission_applications')->where('id', $id)->first();
        
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Application not found'], 404);
        }

        $documents = DB::table('application_documents')->where('application_id', $id)->get();
        $interviews = DB::table('admission_interviews')
                        ->where('application_id', $id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        $application->documents = $documents;
        $application->interviews = $interviews;

        return response()->json([
            'status' => 'success',
            'data' => $application
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        $application = DB::table('admission_applications')->where('id', $id)->first();
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Application not found'], 404);
        }

        $oldStatus = $application->status;
        $newStatus = $request->input('status');
        $remarks = $request->input('remarks');

        DB::transaction(function() use ($id, $oldStatus, $newStatus, $remarks) {
            DB::table('admission_applications')->where('id', $id)->update([
                'status' => $newStatus,
                'status_updated_at' => now(),
                'updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now()
            ]);

            DB::table('application_status_logs')->insert([
                'application_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'remarks' => $remarks,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create system notification
            DB::table('application_notifications')->insert([
                'application_id' => $id,
                'title' => 'Status Updated',
                'message' => "Application status changed from {$oldStatus} to {$newStatus}.",
                'notification_channel' => 'system',
                'notification_type' => 'info',
                'sent_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated successfully'
        ]);
    }

    public function addRemarks(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string'
        ]);

        DB::table('admission_applications')->where('id', $id)->update([
            'remarks' => $request->input('remarks'),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Remarks added successfully'
        ]);
    }

    public function approve(Request $request, $id)
    {
        $application = DB::table('admission_applications')->where('id', $id)->first();
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Application not found'], 404);
        }

        $oldStatus = $application->status;

        DB::transaction(function() use ($id, $oldStatus) {
            DB::table('admission_applications')->where('id', $id)->update([
                'status' => 'accepted',
                'status_updated_at' => now(),
                'updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now()
            ]);

            DB::table('application_status_logs')->insert([
                'application_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => 'accepted',
                'remarks' => 'Application approved',
                'changed_by' => Auth::id(),
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('application_notifications')->insert([
                'application_id' => $id,
                'title' => 'Application Accepted',
                'message' => 'Congratulations, the application has been accepted.',
                'notification_channel' => 'system',
                'notification_type' => 'success',
                'sent_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Application approved successfully'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $application = DB::table('admission_applications')->where('id', $id)->first();
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Application not found'], 404);
        }

        $oldStatus = $application->status;
        $remarks = $request->input('remarks', 'Application rejected');

        DB::transaction(function() use ($id, $oldStatus, $remarks) {
            DB::table('admission_applications')->where('id', $id)->update([
                'status' => 'rejected',
                'status_updated_at' => now(),
                'updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now()
            ]);

            DB::table('application_status_logs')->insert([
                'application_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'remarks' => $remarks,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('application_notifications')->insert([
                'application_id' => $id,
                'title' => 'Application Rejected',
                'message' => 'Unfortunately, the application has been rejected.',
                'notification_channel' => 'system',
                'notification_type' => 'rejected',
                'sent_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Application rejected successfully'
        ]);
    }

    public function requestDocuments(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string'
        ]);

        $application = DB::table('admission_applications')->where('id', $id)->first();
        if (!$application) {
            return response()->json(['status' => 'error', 'message' => 'Application not found'], 404);
        }

        $oldStatus = $application->status;
        $remarks = $request->input('remarks');

        DB::transaction(function() use ($id, $oldStatus, $remarks) {
            DB::table('admission_applications')->where('id', $id)->update([
                'status' => 'documents_required',
                'status_updated_at' => now(),
                'updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now()
            ]);

            DB::table('application_status_logs')->insert([
                'application_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => 'documents_required',
                'remarks' => $remarks,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('application_notifications')->insert([
                'application_id' => $id,
                'title' => 'Documents Required',
                'message' => "Additional documents are required: $remarks",
                'notification_channel' => 'system',
                'notification_type' => 'warning',
                'sent_by' => Auth::id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Documents requested successfully'
        ]);
    }
}
