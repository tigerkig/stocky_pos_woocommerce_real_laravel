<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToAttendancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('attendances', function(Blueprint $table)
		{
			$table->foreign('company_id', 'attendances_company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id', 'attendances_user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('employee_id', 'attendances_employee_id')->references('id')->on('employees')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('attendances', function(Blueprint $table)
		{
			$table->dropForeign('attendances_company_id');
			$table->dropForeign('attendances_user_id');
			$table->dropForeign('attendances_employee_id');
		});
	}

}
