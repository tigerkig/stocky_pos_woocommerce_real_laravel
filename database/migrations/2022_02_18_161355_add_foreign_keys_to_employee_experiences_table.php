<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToEmployeeExperiencesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('employee_experiences', function(Blueprint $table)
		{
			$table->foreign('employee_id', 'employee_experience_employee_id')->references('id')->on('employees')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('employee_experiences', function(Blueprint $table)
		{
			$table->dropForeign('employee_experience_employee_id');
		});
	}

}
