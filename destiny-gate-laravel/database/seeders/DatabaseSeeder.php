<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Staff;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\FeeStructure;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\Announcement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Admin ----
        $admin = User::create([
            'name'     => 'System Administrator',
            'email'    => 'admin@destinygate.ac.zw',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'is_active'=> true,
        ]);

        // ---- Headmaster ----
        $hmUser = User::create([
            'name'     => 'Dr. Emmanuel Chikwanda',
            'email'    => 'headmaster@destinygate.ac.zw',
            'password' => Hash::make('password'),
            'role'     => 'headmaster',
        ]);
        Staff::create([
            'user_id'         => $hmUser->id,
            'staff_id'        => 'STF-0001',
            'first_name'      => 'Emmanuel',
            'last_name'       => 'Chikwanda',
            'email'           => 'headmaster@destinygate.ac.zw',
            'department'      => 'Administration',
            'position'        => 'Headmaster',
            'roles'           => ['headmaster'],
            'employment_date' => '2018-01-15',
        ]);

        // ---- Bursar ----
        $bursarUser = User::create([
            'name'     => 'Mrs. Grace Moyo',
            'email'    => 'bursar@destinygate.ac.zw',
            'password' => Hash::make('password'),
            'role'     => 'bursar',
        ]);
        Staff::create([
            'user_id'    => $bursarUser->id,
            'staff_id'   => 'STF-0002',
            'first_name' => 'Grace',
            'last_name'  => 'Moyo',
            'email'      => 'bursar@destinygate.ac.zw',
            'department' => 'Finance',
            'position'   => 'Bursar',
            'roles'      => ['bursar'],
        ]);

        // ---- Teachers ----
        $teacher1User = User::create([
            'name'     => 'Mr. Tendai Mutasa',
            'email'    => 'tmutasa@destinygate.ac.zw',
            'password' => Hash::make('password'),
            'role'     => 'teacher',
        ]);
        $teacher1 = Staff::create([
            'user_id'    => $teacher1User->id,
            'staff_id'   => 'STF-0003',
            'first_name' => 'Tendai',
            'last_name'  => 'Mutasa',
            'email'      => 'tmutasa@destinygate.ac.zw',
            'department' => 'Sciences',
            'position'   => 'Science Teacher',
            'roles'      => ['teacher'],
        ]);

        $teacher2User = User::create([
            'name'     => 'Ms. Rudo Ncube',
            'email'    => 'rncube@destinygate.ac.zw',
            'password' => Hash::make('password'),
            'role'     => 'teacher',
        ]);
        $teacher2 = Staff::create([
            'user_id'    => $teacher2User->id,
            'staff_id'   => 'STF-0004',
            'first_name' => 'Rudo',
            'last_name'  => 'Ncube',
            'email'      => 'rncube@destinygate.ac.zw',
            'department' => 'Humanities',
            'position'   => 'English Teacher',
            'roles'      => ['teacher'],
        ]);

        // ---- Classes ----
        $year = date('Y') . '/' . (date('Y') + 1);
        $class1 = SchoolClass::create(['class_name' => 'Form 1', 'stream' => 'A', 'academic_year' => $year, 'capacity' => 35, 'class_teacher_id' => $teacher1->id]);
        $class2 = SchoolClass::create(['class_name' => 'Form 2', 'stream' => 'A', 'academic_year' => $year, 'capacity' => 35, 'class_teacher_id' => $teacher2->id]);
        $class3 = SchoolClass::create(['class_name' => 'Form 3', 'stream' => 'B', 'academic_year' => $year, 'capacity' => 30]);

        // ---- Subjects ----
        $math    = Subject::create(['name' => 'Mathematics',     'code' => 'MATH', 'pass_mark' => 50, 'is_compulsory' => true]);
        $english = Subject::create(['name' => 'English Language','code' => 'ENG',  'pass_mark' => 50, 'is_compulsory' => true]);
        $science = Subject::create(['name' => 'Combined Science','code' => 'SCI',  'pass_mark' => 50, 'is_compulsory' => false]);
        $history = Subject::create(['name' => 'History',         'code' => 'HIST', 'pass_mark' => 50, 'is_compulsory' => false]);
        $geo     = Subject::create(['name' => 'Geography',       'code' => 'GEO',  'pass_mark' => 50, 'is_compulsory' => false]);

        // ---- Forms (default Form 1-6) ----
        \Illuminate\Support\Facades\DB::table('forms')->insertOrIgnore([
            ['name'=>'Form 1','level'=>1,'description'=>'Junior Secondary - Year 1','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Form 2','level'=>2,'description'=>'Junior Secondary - Year 2','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Form 3','level'=>3,'description'=>'Junior Secondary - Year 3','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Form 4','level'=>4,'description'=>'Senior Secondary - Year 1','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Form 5','level'=>5,'description'=>'Senior Secondary - Year 2','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Form 6','level'=>6,'description'=>'Advanced Level',           'created_at'=>now(),'updated_at'=>now()],
        ]);
        $this->call(CategorySeeder::class);

        // ---- Teacher-Subject Assignments (skipped — use Teacher Allocation module) ----

        // ---- Students ----
        $parentUser1 = User::create([
            'name'     => 'Mr. John Dube',
            'email'    => 'jdube@gmail.com',
            'password' => Hash::make('password'),
            'role'     => 'parent',
        ]);

        $student1 = Student::create([
            'admission_number' => 'DGI-' . date('Y') . '-0001',
            'first_name'       => 'Tatenda',
            'last_name'        => 'Dube',
            'email'            => 'tatenda.dube@student.destinygate.ac.zw',
            'date_of_birth'    => '2010-03-15',
            'gender'           => 'male',
            'class_id'         => $class1->id,
            'admission_date'   => date('Y') . '-01-10',
            'status'           => 'active',
        ]);

        Guardian::create([
            'user_id'            => $parentUser1->id,
            'student_id'         => $student1->id,
            'first_name'         => 'John',
            'last_name'          => 'Dube',
            'email'              => 'jdube@gmail.com',
            'phone'              => '0771234567',
            'relationship'       => 'Father',
            'is_primary_contact' => true,
        ]);

        $student2 = Student::create([
            'admission_number' => 'DGI-' . date('Y') . '-0002',
            'first_name'       => 'Chiedza',
            'last_name'        => 'Moyo',
            'date_of_birth'    => '2009-07-22',
            'gender'           => 'female',
            'class_id'         => $class2->id,
            'admission_date'   => date('Y') . '-01-10',
            'status'           => 'active',
        ]);

        $student3 = Student::create([
            'admission_number' => 'DGI-' . date('Y') . '-0003',
            'first_name'       => 'Farai',
            'last_name'        => 'Zimba',
            'date_of_birth'    => '2008-11-05',
            'gender'           => 'male',
            'class_id'         => $class3->id,
            'admission_date'   => date('Y') . '-01-10',
            'status'           => 'active',
        ]);

        // ---- Fee Structures ----
        foreach ([$class1->class_name, $class2->class_name, $class3->class_name] as $className) {
            foreach (['term1', 'term2', 'term3'] as $term) {
                FeeStructure::create([
                    'academic_year' => $year,
                    'class_name'    => $className,
                    'term'          => $term,
                    'amount'        => 350.00,
                    'due_date'      => date('Y') . '-' . ($term === 'term1' ? '02-01' : ($term === 'term2' ? '05-01' : '08-01')),
                    'description'   => "School fees for {$className} - " . strtoupper($term),
                ]);
            }
        }

        // ---- Student Fees ----
        $fee1 = StudentFee::create([
            'student_id'    => $student1->id,
            'academic_year' => $year,
            'term'          => 'term1',
            'amount'        => 350.00,
            'amount_paid'   => 350.00,
            'balance'       => 0.00,
            'due_date'      => date('Y') . '-02-01',
            'status'        => 'paid',
        ]);

        $fee2 = StudentFee::create([
            'student_id'    => $student1->id,
            'academic_year' => $year,
            'term'          => 'term2',
            'amount'        => 350.00,
            'amount_paid'   => 150.00,
            'balance'       => 200.00,
            'due_date'      => date('Y') . '-05-01',
            'status'        => 'partial',
        ]);

        StudentFee::create([
            'student_id'    => $student2->id,
            'academic_year' => $year,
            'term'          => 'term1',
            'amount'        => 350.00,
            'amount_paid'   => 0.00,
            'balance'       => 350.00,
            'due_date'      => date('Y') . '-02-01',
            'status'        => 'overdue',
        ]);

        // ---- Payments ----
        Payment::create([
            'payment_number' => 'PAY-' . date('Ymd') . '-0001',
            'student_id'     => $student1->id,
            'student_fee_id' => $fee1->id,
            'amount'         => 350.00,
            'payment_method' => 'cash',
            'payment_date'   => date('Y') . '-01-25',
            'receipt_number' => 'RCP-' . date('Ymd') . '-0001',
            'recorded_by'    => $bursarUser->id,
        ]);

        Payment::create([
            'payment_number' => 'PAY-' . date('Ymd') . '-0002',
            'student_id'     => $student1->id,
            'student_fee_id' => $fee2->id,
            'amount'         => 150.00,
            'payment_method' => 'bank_transfer',
            'payment_date'   => date('Y') . '-05-10',
            'receipt_number' => 'RCP-' . date('Ymd') . '-0002',
            'recorded_by'    => $bursarUser->id,
        ]);

        // ---- Announcements ----
        Announcement::create([
            'title'      => 'Welcome Back to School!',
            'content'    => 'We welcome all students and parents to the new academic year. Please ensure all fees are paid by the due date.',
            'audience'   => 'all',
            'created_by' => $hmUser->id,
            'is_active'  => true,
        ]);

        Announcement::create([
            'title'      => 'Term 2 Fee Reminder',
            'content'    => 'Term 2 fees are due by May 1st. Please contact the bursar for payment arrangements.',
            'audience'   => 'parents',
            'created_by' => $hmUser->id,
            'is_active'  => true,
        ]);

        $this->call(ShopSeeder::class);
        $this->call(BehaviourSeeder::class);

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('  Admin:      admin@destinygate.ac.zw / password');
        $this->command->info('  Headmaster: headmaster@destinygate.ac.zw / password');
        $this->command->info('  Bursar:     bursar@destinygate.ac.zw / password');
        $this->command->info('  Teacher:    tmutasa@destinygate.ac.zw / password');
        $this->command->info('  Parent:     jdube@gmail.com / password');
    }
}
