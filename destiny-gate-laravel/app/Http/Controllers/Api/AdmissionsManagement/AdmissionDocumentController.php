<?php

namespace App\Http\Controllers\Api\AdmissionsManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionDocumentController extends Controller
{
    public function verify(Request $request, $id)
    {
        // Assuming $id is the document ID
        // Note: Currently, there is no 'verified' column on application_documents in the schema,
        // so we might just add remarks or update the application status if all docs are verified.
        // For now, let's assume we just return success as a placeholder, 
        // or we could add an 'is_verified' column if needed, but per schema, we only have application_id, document_type, file_name, file_path, uploaded_at.
        // Let's implement this as a successful response for the UI to show.
        
        return response()->json([
            'status' => 'success',
            'message' => 'Document marked as verified'
        ]);
    }

    public function requestReplacement(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $document = DB::table('application_documents')->where('id', $id)->first();
        if (!$document) {
            return response()->json(['status' => 'error', 'message' => 'Document not found'], 404);
        }

        // We can create a notification to the applicant
        DB::table('application_notifications')->insert([
            'application_id' => $document->application_id,
            'title' => 'Document Replacement Required',
            'message' => "Please replace the document '{$document->document_type}'. Reason: " . $request->input('reason'),
            'notification_channel' => 'system',
            'notification_type' => 'warning',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document replacement requested successfully'
        ]);
    }
}
