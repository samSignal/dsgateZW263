<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('forms')->orderBy('level')->get());
    }

    public function update(Request $request, int $id)
    {
        $form = DB::table('forms')->find($id);
        abort_if(!$form, 404, 'Form not found.');

        $data = $request->validate([
            'name'        => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        DB::table('forms')->where('id', $id)->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'updated_at'  => now(),
        ]);

        return response()->json(DB::table('forms')->find($id));
    }
}
