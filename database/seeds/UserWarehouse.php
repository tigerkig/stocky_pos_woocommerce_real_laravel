<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserWarehouse extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert some stuff
        DB::table('user_warehouse')->insert(
            array(
                'user_id'      => 1,
                'warehouse_id' => 1,
            )
        );
    }
}
