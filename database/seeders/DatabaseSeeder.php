<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Modules\Teacher\Models\Teacher;
use Modules\Classes\Models\{SchoolClass, Section, Room};
use Modules\Subject\Models\Subject;
use Modules\Timetable\Models\Timetable;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'username' => 'admin',
            'name'     => 'Administrator',
            'email'    => 'admin@school.edu',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // Teachers data
        $teachersData = [
            ['Rajesh Kumar',  'rajesh@school.edu',  '9876543210', 'EMP001', 'Mathematics',   'M.Sc Mathematics', 'rajesh'],
            ['Priya Sharma',  'priya@school.edu',   '9876543211', 'EMP002', 'English',        'M.A English',      'priya'],
            ['Amit Singh',    'amit@school.edu',    '9876543212', 'EMP003', 'Science',        'M.Sc Physics',     'amit'],
            ['Sunita Devi',   'sunita@school.edu',  '9876543213', 'EMP004', 'Hindi',          'M.A Hindi',        'sunita'],
            ['Vikram Patel',  'vikram@school.edu',  '9876543214', 'EMP005', 'Social Science', 'M.A History',      'vikram'],
        ];

        foreach ($teachersData as [$name, $email, $phone, $empId, $spec, $qual, $uname]) {
            $teacher = Teacher::create([
                'name'                   => $name,
                'email'                  => $email,
                'phone'                  => $phone,
                'employee_id'            => $empId,
                'subject_specialization' => $spec,
                'qualification'          => $qual,
                'joining_date'           => '2020-06-01',
            ]);

            $user = User::create([
                'username'   => $uname,
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make('teacher123'),
                'role'       => 'teacher',
                'teacher_id' => $teacher->id,
            ]);

            $teacher->update(['user_id' => $user->id]);
        }

        // Classes + Sections
        foreach (['Class 6','Class 7','Class 8','Class 9','Class 10'] as $cname) {
            $class = SchoolClass::create(['class_name' => $cname]);
            Section::create(['class_id' => $class->id, 'section_name' => 'A']);
            Section::create(['class_id' => $class->id, 'section_name' => 'B']);
        }

        // Rooms
        foreach (['101','102','201','202','Lab-1'] as $r) {
            Room::create(['room_number' => $r, 'capacity' => 40]);
        }

        // Subjects
        foreach ([
            ['Mathematics',    'MATH'],
            ['English',        'ENG'],
            ['Science',        'SCI'],
            ['Hindi',          'HIN'],
            ['Social Science', 'SST'],
            ['Computer Science','CS'],
        ] as [$sname, $code]) {
            Subject::create(['subject_name' => $sname, 'subject_code' => $code]);
        }

        // Sample timetable
        $days    = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        $teacher = Teacher::first();
        $class   = SchoolClass::first();
        $section = Section::where('class_id', $class->id)->first();
        $subject = Subject::first();

        foreach (array_slice($days, 0, 3) as $i => $day) {
            try {
                Timetable::create([
                    'class_id'      => $class->id,
                    'section_id'    => $section->id,
                    'subject_id'    => $subject->id,
                    'teacher_id'    => $teacher->id,
                    'day'           => $day,
                    'period_number' => 1,
                    'start_time'    => '08:00',
                    'end_time'      => '08:45',
                ]);
            } catch (\Exception $e) {
                // Skip duplicates
            }
        }
    }
}
