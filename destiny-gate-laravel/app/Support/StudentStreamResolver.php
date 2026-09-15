<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class StudentStreamResolver
{
    public static function resolveByStudentId(int $studentId): ?object
    {
        $student = DB::table('students')->where('id', $studentId)->first();
        if (!$student) {
            return null;
        }

        return self::resolveStudent($student);
    }

    public static function resolveStudent(object $student): object
    {
        $direct = self::directStream($student);
        if ($direct) {
            return self::result($student, $direct, 'stream_id', true);
        }

        $legacy = self::legacyStream($student);
        if ($legacy) {
            return self::result($student, $legacy, 'class_id', true);
        }

        $class = !empty($student->class_id) ? DB::table('classes')->where('id', $student->class_id)->first() : null;

        return (object) [
            'student_id' => $student->id,
            'stream_id' => null,
            'stream_name' => null,
            'form_id' => null,
            'form_name' => null,
            'category_id' => null,
            'category_name' => null,
            'category_code' => null,
            'academic_year_id' => $student->academic_year_id ?? null,
            'class_id' => $student->class_id ?? null,
            'class_name' => $class?->class_name,
            'class_stream' => $class?->stream,
            'display_label' => $class ? trim($class->class_name . ' ' . ($class->stream ?? '')) : null,
            'source' => 'unmapped',
            'is_mapped' => false,
        ];
    }

    public static function mapClass(?int $classId): object
    {
        $class = $classId ? DB::table('classes')->where('id', $classId)->first() : null;
        if (!$class) {
            return (object) ['status' => 'unmapped', 'reason' => 'Class not found.', 'class' => null, 'stream' => null];
        }

        $forms = DB::table('forms')->where('name', $class->class_name)->get();
        if ($forms->count() !== 1) {
            return (object) [
                'status' => $forms->isEmpty() ? 'unmapped' : 'duplicate',
                'reason' => $forms->isEmpty() ? 'No matching form found.' : 'Multiple matching forms found.',
                'class' => $class,
                'stream' => null,
            ];
        }

        $streams = DB::table('streams')->where('form_id', $forms->first()->id)->where('name', $class->stream)->get();
        if ($streams->count() !== 1) {
            return (object) [
                'status' => $streams->isEmpty() ? 'unmapped' : 'duplicate',
                'reason' => $streams->isEmpty() ? 'No matching stream found.' : 'Multiple matching streams found.',
                'class' => $class,
                'stream' => null,
            ];
        }

        return (object) ['status' => 'mapped', 'reason' => null, 'class' => $class, 'stream' => $streams->first()];
    }

    public static function backfillStudents(bool $dryRun = false): array
    {
        $report = [
            'updated' => 0,
            'skipped_existing_stream' => 0,
            'unmapped' => [],
            'duplicates' => [],
            'failed' => [],
        ];

        DB::table('students')->orderBy('id')->chunkById(100, function ($students) use (&$report, $dryRun) {
            foreach ($students as $student) {
                if (!empty($student->stream_id)) {
                    $report['skipped_existing_stream']++;
                    continue;
                }

                $mapping = self::mapClass($student->class_id ?? null);

                if ($mapping->status === 'duplicate') {
                    $report['duplicates'][] = self::mappingRow($student, $mapping);
                    continue;
                }

                if ($mapping->status !== 'mapped') {
                    $report['unmapped'][] = self::mappingRow($student, $mapping);
                    continue;
                }

                $stream = $mapping->stream;
                $updates = [
                    'stream_id' => $stream->id,
                    'form_id' => $stream->form_id,
                    'category_id' => $stream->category_id ?? null,
                    'updated_at' => now(),
                ];

                $academicYearId = self::academicYearIdForClass($mapping->class);
                if ($academicYearId) {
                    $updates['academic_year_id'] = $academicYearId;
                }

                try {
                    if (!$dryRun) {
                        DB::table('students')->where('id', $student->id)->whereNull('stream_id')->update($updates);
                    }
                    $report['updated']++;
                } catch (\Throwable $e) {
                    $row = self::mappingRow($student, $mapping);
                    $row['error'] = $e->getMessage();
                    $report['failed'][] = $row;
                }
            }
        });

        return $report;
    }

    public static function displayLabelForStudentId(int $studentId): ?string
    {
        return self::resolveByStudentId($studentId)?->display_label;
    }

    public static function attachResolvedFields(object $student): object
    {
        $studentId = $student->student_id ?? $student->id ?? null;
        $source = $student;

        if ($studentId && (!property_exists($student, 'class_id') || property_exists($student, 'student_id'))) {
            $source = DB::table('students')->where('id', $studentId)->first() ?: $student;
        }

        $resolved = self::resolveStudent($source);

        $student->stream_id = $resolved->stream_id;
        $student->stream_name = $resolved->stream_name;
        $student->form_id = $resolved->form_id;
        $student->form_name = $resolved->form_name;
        $student->category_id = $resolved->category_id;
        $student->category_name = $resolved->category_name;
        $student->category_code = $resolved->category_code;
        $student->class_id = $student->class_id ?? $resolved->class_id;
        $student->class_name = $resolved->class_name;
        $student->class_stream = $resolved->class_stream;

        $student->resolved_stream_id = $resolved->stream_id;
        $student->resolved_stream_name = $resolved->stream_name;
        $student->resolved_form_id = $resolved->form_id;
        $student->resolved_form_name = $resolved->form_name;
        $student->resolved_category_id = $resolved->category_id;
        $student->resolved_category_name = $resolved->category_name;
        $student->stream = $resolved->stream_name ?? $student->stream ?? null;
        $student->class_display = $resolved->display_label;
        $student->stream_resolution_source = $resolved->source;
        $student->stream_is_mapped = $resolved->is_mapped;

        return $student;
    }

    public static function attachResolvedFieldsToCollection(iterable $students): array
    {
        $rows = [];
        foreach ($students as $student) {
            $rows[] = self::attachResolvedFields($student);
        }

        return $rows;
    }

    public static function studentIdsForStream(int $streamId, ?int $academicYearId = null, bool $activeOnly = true): array
    {
        $yearName = $academicYearId ? DB::table('academic_years')->where('id', $academicYearId)->value('name') : null;

        $direct = DB::table('students')
            ->where('stream_id', $streamId);
        if ($activeOnly) $direct->where('status', 'active');
        if ($academicYearId) {
            $direct->where(function ($q) use ($academicYearId) {
                $q->whereNull('academic_year_id')->orWhere('academic_year_id', $academicYearId);
            });
        }

        $directIds = $direct->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        $legacy = DB::table('students as s')
            ->join('classes as c', 's.class_id', '=', 'c.id')
            ->join('forms as f', 'c.class_name', '=', 'f.name')
            ->join('streams as st', function ($join) {
                $join->on('st.form_id', '=', 'f.id')->on('st.name', '=', 'c.stream');
            })
            ->whereNull('s.stream_id')
            ->where('st.id', $streamId);
        if ($activeOnly) $legacy->where('s.status', 'active');
        if ($yearName) self::whereClassAcademicYear($legacy, $yearName);

        $legacyIds = $legacy->pluck('s.id')->map(fn ($id) => (int) $id)->toArray();

        return array_values(array_unique(array_merge($directIds, $legacyIds)));
    }

    public static function studentIdsForForm(int $formId, ?int $academicYearId = null, bool $activeOnly = true): array
    {
        $yearName = $academicYearId ? DB::table('academic_years')->where('id', $academicYearId)->value('name') : null;

        $direct = DB::table('students as s')
            ->join('streams as st', 's.stream_id', '=', 'st.id')
            ->where('st.form_id', $formId);
        if ($activeOnly) $direct->where('s.status', 'active');
        if ($academicYearId) {
            $direct->where(function ($q) use ($academicYearId) {
                $q->whereNull('s.academic_year_id')->orWhere('s.academic_year_id', $academicYearId);
            });
        }

        $directIds = $direct->pluck('s.id')->map(fn ($id) => (int) $id)->toArray();

        $legacy = DB::table('students as s')
            ->join('classes as c', 's.class_id', '=', 'c.id')
            ->join('forms as f', 'c.class_name', '=', 'f.name')
            ->join('streams as st', function ($join) {
                $join->on('st.form_id', '=', 'f.id')->on('st.name', '=', 'c.stream');
            })
            ->whereNull('s.stream_id')
            ->where('f.id', $formId);
        if ($activeOnly) $legacy->where('s.status', 'active');
        if ($yearName) self::whereClassAcademicYear($legacy, $yearName);

        $legacyIds = $legacy->pluck('s.id')->map(fn ($id) => (int) $id)->toArray();

        return array_values(array_unique(array_merge($directIds, $legacyIds)));
    }

    public static function studentIdsForCategory(int $categoryId, ?int $academicYearId = null, bool $activeOnly = true): array
    {
        $yearName = $academicYearId ? DB::table('academic_years')->where('id', $academicYearId)->value('name') : null;

        $direct = DB::table('students as s')
            ->join('streams as st', 's.stream_id', '=', 'st.id')
            ->where('st.category_id', $categoryId);
        if ($activeOnly) $direct->where('s.status', 'active');
        if ($academicYearId) {
            $direct->where(function ($q) use ($academicYearId) {
                $q->whereNull('s.academic_year_id')->orWhere('s.academic_year_id', $academicYearId);
            });
        }

        $directIds = $direct->pluck('s.id')->map(fn ($id) => (int) $id)->toArray();

        $legacy = DB::table('students as s')
            ->join('classes as c', 's.class_id', '=', 'c.id')
            ->join('forms as f', 'c.class_name', '=', 'f.name')
            ->join('streams as st', function ($join) {
                $join->on('st.form_id', '=', 'f.id')->on('st.name', '=', 'c.stream');
            })
            ->whereNull('s.stream_id')
            ->where('st.category_id', $categoryId);
        if ($activeOnly) $legacy->where('s.status', 'active');
        if ($yearName) self::whereClassAcademicYear($legacy, $yearName);

        $legacyIds = $legacy->pluck('s.id')->map(fn ($id) => (int) $id)->toArray();

        return array_values(array_unique(array_merge($directIds, $legacyIds)));
    }

    private static function directStream(object $student): ?object
    {
        if (empty($student->stream_id)) {
            return null;
        }

        return DB::table('streams as st')
            ->join('forms as f', 'st.form_id', '=', 'f.id')
            ->leftJoin('categories as cat', 'st.category_id', '=', 'cat.id')
            ->where('st.id', $student->stream_id)
            ->select('st.id as stream_id', 'st.name as stream_name', 'f.id as form_id', 'f.name as form_name', 'cat.id as category_id', 'cat.name as category_name', 'cat.code as category_code')
            ->first();
    }

    private static function legacyStream(object $student): ?object
    {
        if (empty($student->class_id)) {
            return null;
        }

        return DB::table('classes as c')
            ->join('forms as f', 'c.class_name', '=', 'f.name')
            ->join('streams as st', function ($join) {
                $join->on('st.form_id', '=', 'f.id')->on('st.name', '=', 'c.stream');
            })
            ->leftJoin('categories as cat', 'st.category_id', '=', 'cat.id')
            ->where('c.id', $student->class_id)
            ->select('st.id as stream_id', 'st.name as stream_name', 'f.id as form_id', 'f.name as form_name', 'cat.id as category_id', 'cat.name as category_name', 'cat.code as category_code', 'c.class_name', 'c.stream as class_stream')
            ->first();
    }

    private static function result(object $student, object $stream, string $source, bool $mapped): object
    {
        return (object) [
            'student_id' => $student->id,
            'stream_id' => $stream->stream_id,
            'stream_name' => $stream->stream_name,
            'form_id' => $stream->form_id,
            'form_name' => $stream->form_name,
            'category_id' => $stream->category_id,
            'category_name' => $stream->category_name,
            'category_code' => $stream->category_code,
            'academic_year_id' => $student->academic_year_id ?? null,
            'class_id' => $student->class_id ?? null,
            'class_name' => $stream->class_name ?? $stream->form_name,
            'class_stream' => $stream->class_stream ?? $stream->stream_name,
            'display_label' => trim($stream->form_name . ' ' . $stream->stream_name),
            'source' => $source,
            'is_mapped' => $mapped,
        ];
    }

    /**
     * classes.academic_year is a school-year span like "2026/2027" while academic_years.name
     * is just the starting year ("2026") — an exact-equality match between the two never
     * fires, silently dropping every legacy (class_id-only) student from bulk by-form/
     * by-stream/by-category lookups. Match by prefix instead: "2026" against "2026/2027".
     */
    private static function whereClassAcademicYear($query, string $yearName): void
    {
        $query->where('c.academic_year', 'like', $yearName . '%');
    }

    private static function academicYearIdForClass(?object $class): ?int
    {
        if (!$class || empty($class->academic_year)) {
            return null;
        }

        return DB::table('academic_years')
            ->whereRaw('? like concat(name, \'%\')', [$class->academic_year])
            ->value('id');
    }

    private static function mappingRow(object $student, object $mapping): array
    {
        return [
            'student_id' => $student->id,
            'class_id' => $student->class_id ?? null,
            'class_name' => $mapping->class?->class_name,
            'class_stream' => $mapping->class?->stream,
            'status' => $mapping->status,
            'reason' => $mapping->reason,
        ];
    }
}
