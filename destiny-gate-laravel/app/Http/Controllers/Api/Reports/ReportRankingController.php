<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportRankingController extends Controller
{
    private function deprecatedJson($payload, string $successor)
    {
        return response()
            ->json($payload)
            ->header('Deprecation', 'true')
            ->header('Link', '<' . $successor . '>; rel="successor-version"');
    }

    private function studentNameExpr(): \Illuminate\Database\Query\Expression
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            return DB::raw("CONCAT(s.first_name,' ',s.last_name) as student_name");
        }
        return DB::raw("TRIM(COALESCE(s.first_name,'') || ' ' || COALESCE(s.last_name,'')) as student_name");
    }

    public function streamRankings(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required',
            'term_id' => 'required',
            'stream_id' => 'required',
        ]);

        $yearId = (int) $data['academic_year_id'];
        $termId = (int) $data['term_id'];
        $streamId = (int) $data['stream_id'];

        $total = (int) DB::table('results_rankings')
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->where('ranking_type', 'stream')
            ->where('ranking_id', $streamId)
            ->whereNull('subject_id')
            ->count();

        $rows = DB::table('results_rankings as rr')
            ->join('students as s', 'rr.student_id', '=', 's.id')
            ->leftJoin('results_aggregates as ra', function ($join) {
                $join->on('ra.student_id', '=', 'rr.student_id')
                    ->on('ra.academic_year_id', '=', 'rr.academic_year_id')
                    ->on('ra.term_id', '=', 'rr.term_id');
            })
            ->leftJoin('forms as f', 'ra.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'ra.stream_id', '=', 'st.id')
            ->leftJoin('report_cards as rc', function ($join) {
                $join->on('rc.student_id', '=', 'rr.student_id')
                    ->on('rc.academic_year_id', '=', 'rr.academic_year_id')
                    ->on('rc.term_id', '=', 'rr.term_id');
            })
            ->where('rr.academic_year_id', $yearId)
            ->where('rr.term_id', $termId)
            ->where('rr.ranking_type', 'stream')
            ->where('rr.ranking_id', $streamId)
            ->whereNull('rr.subject_id')
            ->select(
                'rc.id',
                'rc.report_number',
                'rr.student_id',
                $this->studentNameExpr(),
                's.student_number',
                'f.name as form_name',
                'st.name as stream_name',
                'ra.term_average as overall_average',
                DB::raw('null as overall_grade'),
                'rr.rank as class_position',
                DB::raw((string) $total . ' as stream_total_students'),
                DB::raw('null as performance_trend'),
                DB::raw("'computed' as status"),
                'rr.is_withheld'
            )
            ->orderBy('rr.rank')
            ->orderByDesc('rr.score')
            ->orderBy('rr.student_id')
            ->get();

        $scales = DB::table('grading_scales')
            ->where('is_active', true)
            ->orderByDesc('min_percentage')
            ->get();
        foreach ($rows as $row) {
            if ($row->is_withheld) {
                $row->overall_average = null;
                $row->overall_grade = null;
                $row->class_position = null;
                continue;
            }
            $avg = $row->overall_average !== null ? (float) $row->overall_average : null;
            $grade = null;
            if ($avg !== null) {
                foreach ($scales as $s) {
                    if ($avg >= (float) $s->min_percentage && $avg <= (float) $s->max_percentage) {
                        $grade = $s->grade;
                        break;
                    }
                }
            }
            $row->overall_grade = $grade;
        }

        return $this->deprecatedJson($rows, '/api/stream-native/rankings');
    }

    public function subjectRankings(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required',
            'term_id' => 'required',
            'stream_id' => 'required',
            'subject_id' => 'nullable',
        ]);

        $yearId = (int) $data['academic_year_id'];
        $termId = (int) $data['term_id'];
        $streamId = (int) $data['stream_id'];
        $subjectId = !empty($data['subject_id']) ? (int) $data['subject_id'] : null;

        $q = DB::table('results_rankings as rr')
            ->join('students as s', 'rr.student_id', '=', 's.id')
            ->join('subjects as sub', 'rr.subject_id', '=', 'sub.id')
            ->leftJoin('transcript_subject_history as tsh', function ($join) {
                $join->on('tsh.student_id', '=', 'rr.student_id')
                    ->on('tsh.academic_year_id', '=', 'rr.academic_year_id')
                    ->on('tsh.term_id', '=', 'rr.term_id')
                    ->on('tsh.subject_id', '=', 'rr.subject_id');
            })
            ->where('rr.academic_year_id', $yearId)
            ->where('rr.term_id', $termId)
            ->where('rr.ranking_type', 'subject_stream')
            ->where('rr.ranking_id', $streamId)
            ->select(
                'rr.subject_id',
                'sub.name as subject_name',
                'sub.code as subject_code',
                'rr.student_id',
                $this->studentNameExpr(),
                's.student_number',
                'tsh.subject_average',
                'tsh.grade as subject_grade',
                'rr.rank as subject_position',
                'rr.is_withheld'
            );

        if ($subjectId !== null) {
            $q->where('rr.subject_id', $subjectId);
        }

        $rows = $q
            ->orderBy('sub.name')
            ->orderBy('rr.subject_id')
            ->orderBy('rr.rank')
            ->orderByDesc('rr.score')
            ->orderBy('rr.student_id')
            ->get();

        foreach ($rows as $row) {
            if ($row->is_withheld) {
                $row->subject_average = null;
                $row->subject_grade = null;
                $row->subject_position = null;
            }
        }

        return $this->deprecatedJson($rows, '/api/stream-native/rankings');
    }

    public function topPerformers(Request $request)
    {
        $yearId = $request->academic_year_id ? (int) $request->academic_year_id : null;
        $termId = $request->term_id ? (int) $request->term_id : null;
        $streamId = $request->stream_id ? (int) $request->stream_id : null;
        $limit = (int) ($request->limit ?? 10);

        abort_if(!$yearId || !$termId, 422, 'academic_year_id and term_id are required.');

        $q = DB::table('results_aggregates as ra')
            ->join('students as s', 'ra.student_id', '=', 's.id')
            ->leftJoin('forms as f', 'ra.form_id', '=', 'f.id')
            ->leftJoin('streams as st', 'ra.stream_id', '=', 'st.id')
            ->leftJoin('results_rankings as rr', function ($join) {
                $join->on('rr.student_id', '=', 'ra.student_id')
                    ->on('rr.academic_year_id', '=', 'ra.academic_year_id')
                    ->on('rr.term_id', '=', 'ra.term_id')
                    ->on('rr.ranking_id', '=', 'ra.stream_id')
                    ->where('rr.ranking_type', '=', 'stream')
                    ->whereNull('rr.subject_id');
            })
            ->leftJoin('report_cards as rc', function ($join) {
                $join->on('rc.student_id', '=', 'ra.student_id')
                    ->on('rc.academic_year_id', '=', 'ra.academic_year_id')
                    ->on('rc.term_id', '=', 'ra.term_id');
            })
            ->where('ra.academic_year_id', $yearId)
            ->where('ra.term_id', $termId)
            ->whereNotNull('ra.term_average')
            ->where('ra.is_withheld', false)
            ->select(
                'rc.id',
                'rc.report_number',
                $this->studentNameExpr(),
                's.student_number',
                'f.name as form_name',
                'st.name as stream_name',
                'ra.term_average as overall_average',
                DB::raw('null as overall_grade'),
                'rr.rank as class_position'
            )
            ->orderByDesc('ra.term_average')
            ->orderBy('ra.student_id')
            ->limit($limit);

        if ($streamId) $q->where('ra.stream_id', $streamId);

        $rows = $q->get();
        $scales = DB::table('grading_scales')
            ->where('is_active', true)
            ->orderByDesc('min_percentage')
            ->get();
        foreach ($rows as $row) {
            $avg = $row->overall_average !== null ? (float) $row->overall_average : null;
            $grade = null;
            if ($avg !== null) {
                foreach ($scales as $s) {
                    if ($avg >= (float) $s->min_percentage && $avg <= (float) $s->max_percentage) {
                        $grade = $s->grade;
                        break;
                    }
                }
            }
            $row->overall_grade = $grade;
        }

        return $this->deprecatedJson($rows, '/api/stream-native/results/top-performers');
    }

    public function performanceTrends(Request $request)
    {
        return $this->deprecatedJson([], '/api/stream-native/results/top-performers');
    }
}
