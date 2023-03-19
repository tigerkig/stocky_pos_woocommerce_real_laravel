<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attendances', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->integer('id', true);
			$table->integer('user_id')->index('attendances_user_id');
			$table->integer('company_id')->index('attendances_company_id');
			$table->integer('employee_id')->index('attendances_employee_id');
			$table->date('date');
			$table->string('clock_in', 191);
			$table->string('clock_in_ip', 45);
			$table->string('clock_out', 191);
			$table->string('clock_out_ip', 191);
			$table->boolean('clock_in_out');
			$table->string('depart_early', 191)->default('00:00');
			$table->string('late_time', 191)->default('00:00');
			$table->string('overtime', 191)->default('00:00');
			$table->string('total_work', 191)->default('00:00');
			$table->string('total_rest', 191)->default('00:00');
			$table->string('status', 191)->default('present');
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
		Schema::drop('attendances');
	}

}
