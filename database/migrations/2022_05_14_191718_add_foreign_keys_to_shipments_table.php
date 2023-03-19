<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToShipmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('shipments', function(Blueprint $table)
		{
			$table->foreign('sale_id', 'shipment_sale_id')->references('id')->on('sales')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id', 'shipment_user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('shipments', function(Blueprint $table)
		{
			$table->dropForeign('shipment_sale_id');
			$table->dropForeign('shipment_user_id');
		});
	}

}
