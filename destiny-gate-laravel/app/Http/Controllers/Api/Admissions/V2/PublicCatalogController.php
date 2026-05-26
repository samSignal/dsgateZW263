<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionResponder;
use Illuminate\Support\Facades\DB;

class PublicCatalogController extends Controller
{
    public function academicYears()
    {
        $years = DB::table('academic_years')->orderByDesc('id')->select('id', 'name', 'start_date', 'end_date')->get();
        return AdmissionResponder::ok(['academic_years' => $years]);
    }

    public function forms()
    {
        $forms = DB::table('forms')->orderBy('level')->orderBy('name')->select('id', 'name', 'level')->get();
        return AdmissionResponder::ok(['forms' => $forms]);
    }

    public function categories()
    {
        $categories = DB::table('categories')->where('is_active', true)->orderBy('name')->select('id', 'name', 'code', 'description')->get();
        return AdmissionResponder::ok(['categories' => $categories]);
    }
}

