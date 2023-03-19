<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSmsGatewayToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
        {
            if (! Schema::hasColumn('settings', 'sms_gateway_id')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->engine = 'InnoDB';
                    $table->integer('sms_gateway')->after('default_language')->nullable()->default(1);
                });
            }

        }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
}
