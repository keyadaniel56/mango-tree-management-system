<?php

// Composer: "fzaninotto/faker": "v1.3.0"

use Illuminate\Database\Seeder;
use \App\User;
use App\Institute;
use Faker\Factory as Faker;

class UserTableSeeder extends Seeder {

	public function run()
	{
		$users = DB::table('users');
		if($users->count()==0){		
			User::create(array('firstname'=>'Mr.','lastname'=>'Admin','login'=>'admin','email' => 'admin@school.dev','group'=>'Admin','desc'=>'Admin Details Here',"password"=> Hash::make("123456")));
			User::create(array('firstname'=>'Mr.','lastname'=>'Other','login'=>'other','email' => 'other@school.dev','group'=>'Other','desc'=>'other Deatils Here',"password"=> Hash::make("123456")));
			User::create(array('firstname'=>'Mr.','lastname'=>'kashif','login'=>'ictkashif','email' => 'kashif@ictinnovations.com','group'=>'Admin','desc'=>'admin Deatils Here',"password"=> Hash::make("123456")));
		}


		$institute  = Institute::select('*');
		if($institute->count()==0){
			Institute::create(array('name'=>'The Mango Tree Girls School','establish'=>'2017','email'=>'info@mangotreegirls.ac.ke','web' => 'http://www.mangotreegirls.ac.ke/','phoneNo'=>'254712345678','address'=>'Nairobi, Kenya'));
	    }

	      	$student_path = 'sql/student.sql';
	      	$class_path = 'sql/class.sql';
	      	$section_path = 'sql/section.sql';
	      	$subjects_path = 'sql/subjects.sql';
	      	$marks_path = 'sql/marks.sql';
	      	$grade_path = 'sql/grade.sql';
	      	$teacher_path = 'sql/teacher.sql';

	      	foreach ([
	      		'student.sql' => $student_path,
	      		'class.sql' => $class_path,
	      		'section.sql' => $section_path,
	      		'subjects.sql' => $subjects_path,
	      		'marks.sql' => $marks_path,
	      		'grade.sql' => $grade_path,
	      		'teacher.sql' => $teacher_path,
	      	] as $sqlKey => $sqlPath) {
	      		if (file_exists($sqlPath) && ! $this->sqlTableIsSeeded($sqlKey)) {
	      			DB::unprepared(file_get_contents($sqlPath));
	      			$this->command->info($sqlKey.' table seeded!');
	      		} elseif (! file_exists($sqlPath)) {
	      			$this->command->warn($sqlKey.' not found, skipping');
	      		} else {
	      			$this->command->info($sqlKey.' already seeded, skipping');
	      		}
	      	}
	}

	/**
	 * Best-effort check whether a sql dump has already been applied,
	 * so the seeder can be run more than once.
	 *
	 * @param  string  $sqlKey
	 * @return bool
	 */
	protected function sqlTableIsSeeded($sqlKey)
	{
		$tables = [
			'student.sql' => 'Student',
			'class.sql' => 'Class',
			'section.sql' => 'section',
			'subjects.sql' => 'Subject',
			'marks.sql' => 'marks',
			'grade.sql' => 'GPA',
			'teacher.sql' => 'teacher',
		];

		$table = $tables[$sqlKey] ?? null;
		if (! $table) {
			return false;
		}

		try {
			return DB::table($table)->count() > 0;
		} catch (\Exception $e) {
			return false;
		}
	}
}
