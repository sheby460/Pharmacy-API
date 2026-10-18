<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InitialSuperAdminSeeder extends Seeder
{
    /**
     * Create the first administrator only when credentials are explicitly
     * provided through the deployment environment.
     *
     * This avoids a public registration endpoint or a hard-coded password.
     */
    public function run(): void
    {
        $admin = config('pharmacy.initial_admin', []);
        $username = trim((string) ($admin['username'] ?? ''));
        $password = (string) ($admin['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->command?->warn(
                'Initial super-admin skipped: set PHARMACY_ADMIN_USERNAME and PHARMACY_ADMIN_PASSWORD to bootstrap it.'
            );

            return;
        }

        if (strlen($password) < 12) {
            $this->command?->error(
                'Initial super-admin skipped: PHARMACY_ADMIN_PASSWORD must contain at least 12 characters.'
            );

            return;
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->command?->info(
                'Initial super-admin was not recreated because that username already exists.'
            );

            return;
        }

        $user = User::query()->create([
            'fname' => trim((string) ($admin['fname'] ?? 'System')),
            'mname' => trim((string) ($admin['mname'] ?? '')) ?: null,
            'lname' => trim((string) ($admin['lname'] ?? 'Administrator')),
            'username' => $username,
            'gender' => null,
            'phone' => trim((string) ($admin['phone'] ?? '')) ?: null,
            'email' => trim((string) ($admin['email'] ?? '')) ?: null,
            'password' => $password,
            'is_active' => true,
        ]);

        $superAdminRole = Role::findByName('super-admin', 'web');
        $user->assignRole($superAdminRole);

        $this->command?->info(
            'Initial super-admin account created. Change its password after first sign-in.'
        );
    }
}
