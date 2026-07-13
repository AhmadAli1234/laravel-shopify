<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the /settings login account. Reads from .env (ADMIN_NAME/ADMIN_EMAIL/
 * ADMIN_PASSWORD) so real credentials never need to be hardcoded in source -
 * set them there before running `php artisan db:seed`, or just edit the
 * account afterward. Uses updateOrCreate so it's safe to re-run.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@xilotec.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin@202*#')),
            ]
        );
    }
}
