<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->verified()->create([
                                                            'email' => 'admin@admin.com',
                                                            'password' => bcrypt('admin'),
                                                        ]);
    }
}
