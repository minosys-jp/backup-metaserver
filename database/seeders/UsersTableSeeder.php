<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::insert([
            'id' => 1,
            'tenant_id' => null,
            'email' => 'minoru@minosys.com',
            'name' => '松本実',
            'password' => '$2y$10$pyufHYF1aafbR0flwFprNOtik2eWG59xb4pDfJWaDqk4ubKQi1vnC',
        ]);
    }
}
