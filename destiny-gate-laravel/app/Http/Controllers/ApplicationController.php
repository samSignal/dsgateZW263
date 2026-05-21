<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function create()
    {
        return view('applications.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email',
            'phone'          => 'required|string|max:20',
            'date_of_birth'  => 'required|date',
            'guardian_name'  => 'required|string|max:100',
            'guardian_email' => 'required|email',
            'guardian_phone' => 'required|string|max:20',
            'intended_class' => 'required|string|max:100',
            'academic_year'  => 'required|string|max:20',
        ]);

        $data['application_number'] = 'APP-' . date('Y') . '-' . str_pad(Application::count() + 1, 4, '0', STR_PAD_LEFT);

        $application = Application::create($data);

        return redirect()->route('applications.success', $application)
            ->with('success', 'Application submitted successfully. Your application number is ' . $application->application_number);
    }

    public function success(Application $application)
    {
        return view('applications.success', compact('application'));
    }
}
