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
                'name' => 'Administrator',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'SSO',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'SO',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'SS',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'OE',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'OM',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'SE',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'Controller',
            ],
            [
                'id' => Uuid::uuid4(),
                'name' => 'HR',
            ],
        ]);

        $permissions = [
            [
                'name' => 'show_client',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_client',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'list_roles',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'add_role',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_role',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'delete_role',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'add_occurrence_category',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'list_occurrence_categories',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_occurrence_category',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'list_employee_documents',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'add_employee_document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_employee_document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'list_sop_documents',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'add_sop_document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_sop_document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'delete_sop_document',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'list_incident_types',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'add_incident_type',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_incident_type',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'show_attendance_settings',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'edit_attendance_settings',
                'category' => 'Master Settings'
            ],
            [
                'name' => 'list_employees',
                'category' => 'Users'
            ],
            [
                'name' => 'edit_employee',
                'category' => 'Users'
            ],
            [
                'name' => 'add_employee',
                'category' => 'Users'
            ],
            [
                'name' => 'delete_employee',
                'category' => 'Users'
            ],
            [
                'name' => 'upload_employee_documents',
                'category' => 'Users'
            ],
            [
                'name' => 'list_employee_attendances',
                'category' => 'Attendance & incidents'
            ],
            [
                'name' => 'list_incidents',
                'category' => 'Attendance & incidents'
            ],
            [
                'name' => 'edit_incident',
                'category' => 'Attendance & incidents'
            ],
            [
                'name' => 'list_sites',
                'category' => 'Site'
            ],
            [
                'name' => 'add_site',
                'category' => 'Site'
            ],
            [
                'name' => 'list_site_routes',
                'category' => 'Site'
            ],
            [
                'name' => 'add_site_route',
                'category' => 'Site'
            ],
            [
                'name' => 'edit_site_route',
                'category' => 'Site'
            ],
            [
                'name' => 'edit_site',
                'category' => 'Site'
            ],
            [
                'name' => 'delete_site',
                'category' => 'Site'
            ],
            [
                'name' => 'site_map',
                'category' => 'Site'
            ],
            [
                'name' => 'site_allocation',
                'category' => 'Site'
            ],
            [
                'name' => 'guard_tour',
                'category' => 'Site'
            ],
            [
                'name' => 'reports',
                'category' => 'Site'
            ],
            [
                'name' => 'list_e-occurrences',
                'category' => 'e-Occurrence'
            ],
            [
                'name' => 'add_e-occurrence',
                'category' => 'e-Occurrence'
            ],
            [
                'name' => 'edit_e-occurrence',
                'category' => 'e-Occurrence'
            ],
            [
                'name' => 'list_reports',
                'category' => 'Report'
            ],
            [
                'name' => 'list_audit_trails',
                'category' => 'Audit Trail'
            ],
        ];

        $role = Role::where('name', 'Administrator')->first();

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
            'nric_fin_no' => '111111',
            'mobile' => '+1234567890',
            'address' => 'Singapore',
            'briefing_date' => Carbon::now(),
            'date_joined' => Carbon::now(),
            'briefing_conducted' => true,
            'q1' => true,
            'a1' => '',
            'q2' => true,
            'a2' => '',
            'q3' => true,
            'a3' => '',
            'q4' => true,
            'a4' => '',
            'q5' => true,
            'a5' => '',
            'q6' => true,
            'a6' => '',
            'q7' => true,
            'a7' => '',
            'q8' => true,
            'a8' => '',
            'q9' => true,
            'a9' => '',
            'password' => Hash::make('admin123'),
            'id_role' => $role->id,
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
