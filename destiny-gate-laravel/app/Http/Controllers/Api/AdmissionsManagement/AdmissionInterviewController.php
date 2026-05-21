<?php

namespace App\Http\Controllers\Api\AdmissionsManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdmissionInterviewController extends Controller
{
    public function schedule(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:admission_applications,id',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
            'interviewer_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        $applicationId = $request->input('application_id');

        DB::transaction(function() use ($request, $applicationId) {
            DB::table('admission_interviews')->insert([
                'application_id' => $applicationId,
                'interview_date' => $request->input('interview_date'),
                'interview_time' => $request->input('interview_time'),
                'interviewer_id' => $request->input('interviewer_id'),
                'notes' => $request->input('notes'),
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $application = DB::table('admission_applications')->where('id', $applicationId)->first();
            $oldStatus = $application->status;

            DB::table('admission_applications')->where('id', $applicationId)->update([
                'status' => 'interview_scheduled',
                'status_updated_at' => now(),
                'updated_at' => now(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now()
            ]);

            DB::table('application_status_logs')->insert([
                'application_id' => $applicationId,
                'old_status' => $oldStatus,
                'new_status' => 'interview_scheduled',
                'remarks' => 'Interview scheduled for ' . $request->input('interview_date') . ' at ' . $request->input('interview_time'),
                'changed_by' => Auth::id(),
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('application_notifications')->insert([
                'application_id' => $applicationId,
                'title' => 'Interview Scheduled',
                'message' => 'Your interview has been scheduled for ' . $request->input('interview_date') . ' at ' . $request->input('interview_time'),
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
            'message' => 'Interview scheduled successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'interview_date' => 'required|date',
            'interview_time' => 'required',
            'interviewer_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,cancelled,no_show'
        ]);

        DB::table('admission_interviews')->where('id', $id)->update([
            'interview_date' => $request->input('interview_date'),
            'interview_time' => $request->input('interview_time'),
            'interviewer_id' => $request->input('interviewer_id'),
            'notes' => $request->input('notes'),
            'status' => $request->input('status'),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Interview updated successfully'
        ]);
    }

    public function cancel(Request $request, $id)
    {
        DB::table('admission_interviews')->where('id', $id)->update([
            'status' => 'cancelled',
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Interview cancelled successfully'
        ]);
    }
}
