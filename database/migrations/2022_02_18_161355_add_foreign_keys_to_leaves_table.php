<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToLeavesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('leaves', function(Blueprint $table)
		{
			$table->foreign('company_id', 'leave_company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('department_id', 'leave_department_id')->references('id')->on('departments')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('employee_id', 'leave_employee_id')->references('id')->on('employees')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('leave_type_id', 'leave_leave_type_id')->references('id')->on('leave_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('leaves', function(Blueprint $table)
		{
			$table->dropForeign('leave_company_id');
			$table->dropForeign('leave_department_id');
			$table->dropForeign('leave_employee_id');
			$table->dropForeign('leave_leave_type_id');
		});
	}

}
