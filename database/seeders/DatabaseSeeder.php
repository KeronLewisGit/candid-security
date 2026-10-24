<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe: loads the recruiter email templates (only into an empty
     * table) and creates the first admin when ADMIN_EMAIL and ADMIN_PASSWORD are set.
     */
    public function run(): void
    {
        $this->call(TemplateSeeder::class);

        $email = config('candid.admin.email');
        $password = config('candid.admin.password');

        if ($email && $password) {
            User::firstOrCreate(['email' => strtolower((string) $email)], [
                'name' => config('candid.admin.name'),
                'password' => Hash::make((string) $password),
                'role' => 'admin',
                'is_active' => true,
            ]);
        }
    }
}
