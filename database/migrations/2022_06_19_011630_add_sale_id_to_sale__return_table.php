<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaleIdToSaleReturnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('sale_id')->nullable()->after('Ref')->index('sale_id_return_sales');
            $table->foreign('sale_id', 'sale_id_return_sales')->references('id')->on('sales')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropForeign('sale_id_return_sales');
        });
    }
}
