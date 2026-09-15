<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed classes, subjects, sections, teachers, permissions and sample
     * timetable entries so the timetable module is usable out of the box.
     *
     * All inserts are guarded by count checks so the seeder stays idempotent.
     */
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---------------------------------------------------------------
        // Classes (Kenyan secondary school: Form One - Form Four)
        // ---------------------------------------------------------------
        if (DB::table('Class')->count() == 0) {
            $classes = [
                ['code' => 'F1', 'name' => 'Form One',        'description' => 'First year of secondary school'],
                ['code' => 'F2', 'name' => 'Form Two',        'description' => 'Second year of secondary school'],
                ['code' => 'F3', 'name' => 'Form Three',      'description' => 'Third year of secondary school'],
                ['code' => 'F4', 'name' => 'Form Four',       'description' => 'Fourth year of secondary school'],
            ];
            DB::table('Class')->insert(array_map(function ($c) use ($now) {
                return $c + ['created_at' => $now, 'updated_at' => $now];
            }, $classes));
            $this->command->info('Classes seeded!');
        }

        // ---------------------------------------------------------------
        // Subjects (Kenyan secondary school subjects, one set per form)
        // ---------------------------------------------------------------
        if (DB::table('Subject')->count() == 0) {
            $subjectTpl = [
                ['code' => 'ENG', 'name' => 'English'],
                ['code' => 'KIS', 'name' => 'Kiswahili'],
                ['code' => 'MAT', 'name' => 'Mathematics'],
                ['code' => 'BIO', 'name' => 'Biology'],
                ['code' => 'CHE', 'name' => 'Chemistry'],
                ['code' => 'PHY', 'name' => 'Physics'],
                ['code' => 'GEO', 'name' => 'Geography'],
                ['code' => 'HIS', 'name' => 'History and Government'],
                ['code' => 'CRE', 'name' => 'Christian Religious Education'],
                ['code' => 'BUS', 'name' => 'Business Studies'],
                ['code' => 'HOM', 'name' => 'Home Science'],
                ['code' => 'AGR', 'name' => 'Agriculture'],
                ['code' => 'CMP', 'name' => 'Computer Studies'],
                ['code' => 'FRN', 'name' => 'French'],
            ];
            $rows = [];
            foreach (DB::table('Class')->get() as $class) {
                foreach ($subjectTpl as $s) {
                    $rows[] = [
                        'code' => $s['code'],
                        'name' => $s['name'],
                        'type' => 'Theory',
                        'stdgroup' => '1',
                        'subgroup' => '1',
                        'class' => $class->code,
                        'gradeSystem' => '1',
                        'totalfull' => 100,
                        'totalpass' => 40,
                        'wfull' => 0,
                        'wpass' => 0,
                        'mfull' => 100,
                        'mpass' => 40,
                        'sfull' => 0,
                        'spass' => 0,
                        'pfull' => 0,
                        'ppass' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            DB::table('Subject')->insert($rows);
            $this->command->info('Subjects seeded!');
        }

        // ---------------------------------------------------------------
        // Teachers
        // ---------------------------------------------------------------
        if (DB::table('teacher')->count() == 0) {
            $teachers = [
                ['firstName' => 'Ahmad', 'lastName' => 'Raza',   'gender' => 'Male',   'phone' => '03001234567', 'email' => 'ahmad@school.dev',    'fatherName' => 'Raza Ali',    'fatherCellNo' => '03001234560', 'presentAddress' => 'Multan',    'parmanentAddress' => 'Multan'],
                ['firstName' => 'Sana',   'lastName' => 'Khan',   'gender' => 'Female', 'phone' => '03001234568', 'email' => 'sana@school.dev',    'fatherName' => 'Khan Sahab',  'fatherCellNo' => '03001234561', 'presentAddress' => 'Lahore',    'parmanentAddress' => 'Lahore'],
                ['firstName' => 'Bilal',  'lastName' => 'Ahmed',  'gender' => 'Male',   'phone' => '03001234569', 'email' => 'bilal@school.dev',   'fatherName' => 'Ahmed Jan',   'fatherCellNo' => '03001234562', 'presentAddress' => 'Karachi',   'parmanentAddress' => 'Karachi'],
                ['firstName' => 'Mariam', 'lastName' => 'Fatima', 'gender' => 'Female', 'phone' => '03001234570', 'email' => 'mariam@school.dev',  'fatherName' => 'Fatima Bhai', 'fatherCellNo' => '03001234563', 'presentAddress' => 'Islamabad', 'parmanentAddress' => 'Islamabad'],
                ['firstName' => 'Usman',  'lastName' => 'Ali',    'gender' => 'Male',   'phone' => '03001234571', 'email' => 'usman@school.dev',   'fatherName' => 'Ali Abbas',    'fatherCellNo' => '03001234564', 'presentAddress' => 'Faisalabad','parmanentAddress' => 'Faisalabad'],
                ['firstName' => 'Hina',   'lastName' => 'Shah',   'gender' => 'Female', 'phone' => '03001234572', 'email' => 'hina@school.dev',    'fatherName' => 'Shah Nawaz',  'fatherCellNo' => '03001234565', 'presentAddress' => 'Peshawar',  'parmanentAddress' => 'Peshawar'],
            ];
            DB::table('teacher')->insert(array_map(function ($t) use ($now) {
                return $t + ['created_at' => $now, 'updated_at' => $now];
            }, $teachers));
            $this->command->info('Teachers seeded!');
        }

        // ---------------------------------------------------------------
        // Sections (A and B for every class)
        // ---------------------------------------------------------------
        if (DB::table('section')->count() == 0) {
            $firstTeacher = DB::table('teacher')->value('id');
            $rows = [];
            foreach (DB::table('Class')->get() as $class) {
                foreach (['A', 'B'] as $sec) {
                    $rows[] = [
                        'name' => $class->name.' - Section '.$sec,
                        'description' => $class->name.' section '.$sec,
                        'class_code' => $class->code,
                        'teacher_id' => $firstTeacher,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            DB::table('section')->insert($rows);
            $this->command->info('Sections seeded!');
        }

        // ---------------------------------------------------------------
        // Permissions (full set for the Admin group)
        // ---------------------------------------------------------------
        $permissionNames = [
            'class_add', 'class_update', 'class_delete', 'class_view',
            'section_add', 'section_update', 'section_delete', 'section_view', 'section_time_table',
            'subject_add', 'subject_update', 'subject_delete', 'subject_view',
            'teacher_add', 'teacher_update', 'teacher_delete', 'teacher_view', 'teacher_bulk_add',
            'teacher_timetable_add', 'teacher_timetable_view',
            'student_add', 'student_update', 'student_delete', 'student_view', 'student_info',
            'student_student_bulk_add', 'student_student_portal_access', 'student_bulk_add',
            'exam_add', 'exam_update', 'exam_delete', 'exam_view',
            'paper_add', 'paper_update', 'paper_delete', 'paper_view',
            'gpa_rule_add', 'gpa_rule_update', 'gpa_rule_delete', 'gpa_rule_view',
            'accounting', 'view_fess', 'add_fess', 'update_fess', 'delete_fess',
            'view_marks', 'add_marks', 'update_marks', 'delete_marks',
            'view_student_attendance', 'add_student_attendance',
            'view_student_monthly_reports',
            'send_notification', 'promote_student', 'generate_result',
        ];
        if (DB::table('permission')->where('permission_group', 'admin')->count() == 0) {
            $rows = [];
            foreach ($permissionNames as $name) {
                $rows[] = [
                    'permission_name' => $name,
                    'permission_group' => 'admin',
                    'permission_type' => 'yes',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('permission')->insert($rows);
            $this->command->info('Permissions seeded!');
        }

        // ---------------------------------------------------------------
        // Sample timetable entries (First Term and Second Term)
        // ---------------------------------------------------------------
        if (DB::table('timetable')->count() == 0) {
            $teachers = DB::table('teacher')->get();
            $sections = DB::table('section')->get();
            $subjects = DB::table('Subject')->get();
            $dayColor = [
                'monday' => '#5bc0de',
                'tuesday' => '#5cb85c',
                'wednesday' => '#f0ad4e',
                'thursday' => '#d9534f',
                'friday' => '#428bca',
            ];
            $slots = [
                ['08:00 AM', '09:00 AM'],
                ['09:00 AM', '10:00 AM'],
                ['10:15 AM', '11:15 AM'],
                ['11:15 AM', '12:15 PM'],
                ['12:30 PM', '01:30 PM'],
            ];
            // Term 1 and Term 2 use slightly different subject sets so each
            // term has its own timetable.
            $termSubjectOffset = ['1' => 0, '2' => 3];
            $rows = [];
            $i = 0;
            foreach ($sections->take(3) as $section) {
                $classCode = $section->class_code;
                $classSubjects = $subjects->where('class', $classCode)->values();
                foreach ($termSubjectOffset as $term => $offset) {
                    for ($d = 0; $d < 5; $d++) {
                        $teacher = $teachers[$i % $teachers->count()];
                        $subject = $classSubjects->count() ? $classSubjects[($d + $offset) % $classSubjects->count()] : null;
                        if (! $subject) {
                            continue;
                        }
                        $rows[] = [
                            'teacher_id' => $teacher->id,
                            'class_id' => $classCode,
                            'section_id' => $section->id,
                            'subject_id' => $subject->id,
                            'stattime' => $slots[$d % count($slots)][0],
                            'endtime' => $slots[$d % count($slots)][1],
                            'day' => array_keys($dayColor)[$d],
                            'term' => $term,
                            'color' => array_values($dayColor)[$d],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $i++;
                    }
                }
            }
            DB::table('timetable')->insert($rows);
            $this->command->info('Sample timetables seeded!');
        }
    }
}