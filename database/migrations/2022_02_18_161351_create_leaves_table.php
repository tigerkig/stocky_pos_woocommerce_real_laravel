<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeavesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('leaves', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->integer('id', true);
			$table->integer('employee_id')->index('leave_employee_id');
			$table->integer('company_id')->index('leave_company_id');
			$table->integer('department_id')->index('leave_department_id');
			$table->integer('leave_type_id')->index('leave_leave_type_id');
			$table->date('start_date');
			$table->date('end_date');
			$table->string('days', 192);
			$table->text('reason')->nullable();
			$table->string('attachment', 192)->nullable();
			$table->boolean('half_day')->nullable();
			$table->string('status', 192);
			$table->timestamps(6);
			$table->softDeletes();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('leaves');
	}

}
