<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsGatewayTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sms_gateway', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->integer('id', true);
			$table->string('title', 192);
			$table->timestamps(6);
			$table->softDeletes();
		});

		 // Insert some stuff
         DB::table('sms_gateway')->insert(
			array(
                [
                    'id' => 1,
                    'title' => 'twilio',
                ],
                [
                    'id' => 2,
                    'title' => 'nexmo',
                ],
            )
        );
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('sms_gateway');
	}

}
