<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use App\Models\ClientInfo;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            [
                'id' => Uuid::uuid4(),
                'name' => 'Admin',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'Manager',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'Supervisor',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'Guard',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'Employee',
            ],
        ]);

        $permissions = [
            [
                'name' => 'Client',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Add role',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Edit role',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Delete role',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Add occurence catg.',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Edit occurence catg.',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Add employee document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Edit employee document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'List SOP document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Add SOP document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Edit SOP document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'List incident type',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Add incident type',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Edit incident type',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'Attendance setings',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'List employees',
                'category' => 'Users'
            ],
            [
                'name' => 'Edit employees',
                'category' => 'Users'
            ],
            [
                'name' => 'List employee attendance',
                'category' => 'Attendance & incidents'
            ],
            [
                'name' => 'List incidents',
                'category' => 'Attendance & incidents'
            ],
            [
                'name' => 'Edit incidents',
                'category' => 'Attendance & incidents'
            ],
            [
                'name' => 'List sites',
                'category' => 'Site'
            ],
            [
                'name' => 'Add sites',
                'category' => 'Site'
            ],
            [
                'name' => 'Edit sites',
                'category' => 'Site'
            ],
            [
                'name' => 'Delete sites',
                'category' => 'Site'
            ],
            [
                'name' => 'Site map',
                'category' => 'Site'
            ],
            [
                'name' => 'Site allocation',
                'category' => 'Site'
            ],
            [
                'name' => 'Guard tour',
                'category' => 'Site'
            ],
            [
                'name' => 'Reports',
                'category' => 'Site'
            ],
            [
                'name' => 'List e-Occurrence',
                'category' => 'e-Occurrence'
            ],
            [
                'name' => 'Add e-Occurrence',
                'category' => 'e-Occurrence'
            ],
            [
                'name' => 'Edit e-Occurrence',
                'category' => 'e-Occurrence'
            ],
        ];

        $role = Role::where('name', 'Admin')->first();

        foreach ($permissions as $item) {
            Permission::create([
                'id' => Uuid::uuid4(),
                'name' => $item['name'],
                'category' => $item['category'],
            ]);
        }

        $permissionsData = Permission::all();

        foreach ($permissionsData as $item) {
            RolePermission::create([
                'id' => Uuid::uuid4(),
                'id_role' => $role->id,
                'id_permission' => $item->id
            ]);
        }

        $user_id = Uuid::uuid4();

        User::create([
            'id' => $user_id,
            'name' => 'Admin User',
            'email' => 'admin@sentinel360.com',
            'password' => Hash::make('admin123'),
            'id_role' => $role->id,
            'mobile' => '+1234567890',
        ]);

        $employee_id = Uuid::uuid4();

        Employee::create([
            'id' => $employee_id,
            'nric_fin_no' => '111111',
            'briefing_date' => Carbon::now(),
            'id_user' => $user_id,
        ]);

        ClientInfo::create([
            'name' => 'Sentinel Security Agency',
            'reg_no' => '100302565E',
            'address' => '8 UBi Avenue, #04-08, UBi Avenue, 609964',
            'contact' => '84749693',
            'email' => 'info@sentinelgp.com',
            'website' => 'www.sentinelgp.com'
        ]);

        AttendanceSetting::create([
            'grace_period' => 15,
            'geo_fencing' => 200,
            'day_shift_start_time' => '08:00',
            'day_shift_end_time' => '20:00',
            'night_shift_start_time' => '20:00',
            'night_shift_end_time' => '08:00',
            'relief_day_shift_start_time' => '08:00',
            'relief_day_shift_end_time' => '20:00',
            'relief_night_shift_start_time' => '20:00',
            'relief_night_shift_end_time' => '08:00',
        ]);
    }
}
