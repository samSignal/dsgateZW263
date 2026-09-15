<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard-view',
            // Academic Structure
            'academic-years-list','academic-years-create','academic-years-edit','academic-years-delete','academic-years-activate',
            'terms-list','terms-create','terms-edit','terms-delete','terms-set-current',
            'forms-list','forms-edit',
            'streams-list','streams-create','streams-edit','streams-delete',
            'subject-groups-list','subject-groups-create','subject-groups-edit','subject-groups-delete',
            'subjects-list','subjects-create','subjects-edit','subjects-delete',
            'teacher-allocation-list','teacher-allocation-create','teacher-allocation-delete',
            // Staff
            'departments-list','departments-create','departments-edit','departments-delete',
            'staff-list','staff-create','staff-edit','staff-show','staff-delete',
            'staff-activate','staff-suspend','staff-resign','staff-assign-role',
            'teacher-profiles-list','teacher-profiles-create','teacher-profiles-edit',
            // Students
            'students-list','students-create','students-edit','students-show','students-delete',
            'guardians-list','guardians-create','guardians-edit','guardians-delete',
            // Finance
            'fees-list','fees-create','fees-edit',
            'payments-list','payments-create',
            'fee-structures-list','fee-structures-create','fee-structures-edit','fee-structures-delete',
            'finance-reports-view',
            'finance.reverse-payment',
            // School Shop
            'shop.view','shop.manage_categories','shop.manage_items','shop.record_purchase',
            'shop.record_payment','shop.cancel_purchase','shop.view_reports','shop.parent_view',
            // Attendance, Behaviour, Discipline
            'attendance.view','attendance.mark','attendance.submit','attendance.edit_submitted','attendance.reports',
            'behaviour.view','behaviour.manage_categories','behaviour.record_incident','behaviour.review_incident',
            'discipline.create_action','discipline.manage_actions','discipline.reports',
            'notifications.view','parent.attendance_view','parent.behaviour_view','student.attendance_view','student.behaviour_view',
            // Academic Progress Foundation
            'academics.assign_stream_subjects','academics.allocate_teachers','academics.enrol_student_subjects',
            'academics.drop_subjects','academics.view_subject_reports',
            // Assessment Module
            'assessments.view','assessments.manage_types','assessments.create','assessments.enter_marks',
            'assessments.submit_marks','assessments.approve_marks','assessments.reopen_marks','assessments.reports',
            'parent.assessment_view','student.assessment_view',
            // Report Cards
            'reports.generate','reports.publish','reports.approve','reports.view','reports.download','reports.batch_print',
            'parent.report_view','student.report_view',
            // Timetable
            'timetable.manage_periods','timetable.manage_rooms','timetable.manage','timetable.view','timetable.export',
            'teacher.timetable_view','student.timetable_view','parent.timetable_view',
            // Academics
            'marks-list','marks-create','marks-edit',
            'attendance-list','attendance-create','attendance-edit',
            'behaviour-list','behaviour-create','behaviour-edit','behaviour-review',
            'teacher-comments-list','teacher-comments-create','teacher-comments-edit',
            // Reports
            'reports-academic','reports-financial','reports-attendance','reports-behaviour',
            // Communication
            'announcements-list','announcements-create','announcements-edit','announcements-delete',
            // Users & Roles
            'users-list','users-create','users-edit','users-show',
            'roles-list','roles-create','roles-edit','roles-delete',
            'permissions-list','permissions-assign',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ADMIN — full access
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // HEADMASTER
        $headmaster = Role::firstOrCreate(['name' => 'headmaster', 'guard_name' => 'web']);
        $headmaster->syncPermissions([
            'dashboard-view',
            'academic-years-list','terms-list','forms-list','streams-list',
            'subject-groups-list','subjects-list','teacher-allocation-list',
            'departments-list','staff-list','staff-show','teacher-profiles-list',
            'students-list','students-show','students-create','students-edit',
            'guardians-list','guardians-create',
            'fees-list','payments-list','fee-structures-list','finance-reports-view',
            'shop.view_reports',
            'attendance.reports','behaviour.review_incident','discipline.manage_actions','discipline.reports','notifications.view',
            'academics.view_subject_reports',
            'assessments.view','assessments.approve_marks','assessments.reopen_marks','assessments.reports',
            'reports.generate','reports.publish','reports.approve','reports.view','reports.download','reports.batch_print',
            'timetable.manage_periods','timetable.manage_rooms','timetable.manage','timetable.view','timetable.export',
            'marks-list','attendance-list','behaviour-list','behaviour-review',
            'teacher-comments-list',
            'reports-academic','reports-financial','reports-attendance','reports-behaviour',
            'announcements-list','announcements-create','announcements-edit','announcements-delete',
            'shop.view_reports',
        ]);

        // TEACHER
        $teacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher->syncPermissions([
            'dashboard-view',
            'students-list','students-show',
            'marks-list','marks-create','marks-edit',
            'attendance-list','attendance-create','attendance-edit',
            'behaviour-list','behaviour-create',
            'teacher-comments-list','teacher-comments-create','teacher-comments-edit',
            'attendance.view','attendance.mark','attendance.submit',
            'behaviour.view','behaviour.record_incident',
            'academics.view_subject_reports',
            'assessments.view','assessments.create','assessments.enter_marks','assessments.submit_marks',
            'reports.view',
            'teacher.timetable_view',
            'announcements-list',
        ]);

        // BURSAR
        $bursar = Role::firstOrCreate(['name' => 'bursar', 'guard_name' => 'web']);
        $bursar->syncPermissions([
            'dashboard-view',
            'students-list','students-show',
            'fees-list','fees-create','fees-edit',
            'payments-list','payments-create',
            'fee-structures-list','fee-structures-create','fee-structures-edit',
            'finance-reports-view',
            'shop.view','shop.record_purchase','shop.record_payment','shop.view_reports',
            'announcements-list',
        ]);

        // STOREKEEPER
        $storekeeper = Role::firstOrCreate(['name' => 'storekeeper', 'guard_name' => 'web']);
        $storekeeper->syncPermissions([
            'dashboard-view',
            'students-list','students-show',
            'shop.view','shop.manage_items','shop.record_purchase',
            'announcements-list',
        ]);

        // PARENT
        $parent = Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);
        $parent->syncPermissions(['dashboard-view','announcements-list','shop.parent_view','notifications.view','parent.attendance_view','parent.behaviour_view','academics.view_subject_reports','parent.assessment_view','parent.report_view','parent.timetable_view']);

        // STUDENT
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions(['dashboard-view','announcements-list','notifications.view','student.attendance_view','student.behaviour_view','academics.view_subject_reports','student.assessment_view','student.report_view','student.timetable_view']);

        // USER
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->syncPermissions(['dashboard-view']);

        $this->command->info('Permissions seeded: ' . Permission::where('guard_name','web')->count());
        $this->command->info('Roles seeded: ' . Role::where('guard_name','web')->count());
    }
}
