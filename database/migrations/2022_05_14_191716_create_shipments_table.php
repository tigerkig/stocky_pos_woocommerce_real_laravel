<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateShipmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('shipments', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->integer('id', true);
			$table->integer('user_id')->index('shipment_user_id');
			$table->timestamp('date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->string('Ref', 192);
			$table->integer('sale_id')->index('shipment_sale_id');
			$table->string('delivered_to', 192)->nullable();
			$table->text('shipping_address')->nullable();
			$table->string('status', 192);
			$table->text('shipping_details')->nullable();
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
		Schema::drop('shipments');
	}

}
