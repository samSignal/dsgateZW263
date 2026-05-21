<?php

namespace App\Http\Controllers\Api\Admissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdmissionNotificationController extends Controller
{
    public function index(int $id)
    {
        return response()->json(
            DB::table('application_notifications')
                ->where('application_id', $id)
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function sendEmail(Request $request, int $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:150',
            'message' => 'nullable|string',
        ]);

        $app = DB::table('admission_applications')->where('id', $id)->first();
        if (!$app) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        $title = $request->input('title', 'DestinyGate Institute Admission Application');
        $body = $request->input('message') ?: $this->tokenMessage($app);

        try {
            Mail::raw($body, function ($message) use ($app, $title) {
                $message->to($app->guardian_email)->subject($title);
            });
        } catch (\Throwable $e) {
            Log::warning('Admission email send failed: ' . $e->getMessage());
        }

        $this->store($id, $title, $body, 'email', 'info');

        return response()->json(['message' => 'Email notification recorded for delivery.']);
    }

    public function queueWhatsApp(Request $request, int $id)
    {
        $request->validate([
            'message' => 'nullable|string',
        ]);

        $app = DB::table('admission_applications')->where('id', $id)->first();
        if (!$app) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        $message = $request->input('message') ?: "Your DestinyGate Institute application has been received.\n\n"
            . "Application Number:\n{$app->application_number}\n\n"
            . "Tracking Token:\n{$app->tracking_token}\n\n"
            . "Track application:\n" . url('/admissions/track') . "\n\n"
            . "Continue application:\n" . url('/admissions/continue/' . $app->tracking_token);

        $this->store($id, 'WhatsApp Message Ready', $message, 'whatsapp', 'info');

        return response()->json(['message' => 'WhatsApp-ready notification queued.', 'whatsapp_message' => $message]);
    }

    private function tokenMessage(object $app): string
    {
        return "DestinyGate Institute Admission Application\n\n"
            . "Student: {$app->student_first_name} {$app->student_last_name}\n"
            . "Application Number: {$app->application_number}\n"
            . "Tracking Token: {$app->tracking_token}\n\n"
            . "Track application: " . url('/admissions/track') . "\n"
            . "Continue application: " . url('/admissions/continue/' . $app->tracking_token) . "\n\n"
            . "Support: admissions@destinygate.ac.zw";
    }

    private function store(int $id, string $title, string $message, string $channel, string $type): void
    {
        DB::table('application_notifications')->insert([
            'application_id' => $id,
            'title' => $title,
            'message' => $message,
            'notification_channel' => $channel,
            'notification_type' => $type,
            'sent_by' => Auth::id(),
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
