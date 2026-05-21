<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            'admin'      => redirect()->route('admin.dashboard'),
            'headmaster' => redirect()->route('headmaster.dashboard'),
            'teacher'    => redirect()->route('teacher.dashboard'),
            'bursar'     => redirect()->route('bursar.dashboard'),
            'parent'     => redirect()->route('parent.portal'),
            'student'    => redirect()->route('student.portal'),
            default      => view('dashboard.default', compact('user')),
        };
    }
}
