<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Activity;
use App\Models\PortalNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = collect([
            ['Admin PoliBest', 'admin@polibest.test', 'admin'],
            ['Bendahari PoliBest', 'treasurer@polibest.test', 'treasurer'],
            ['Pengerusi PoliBest', 'chairman@polibest.test', 'chairman'],
            ['Muhammad Hilmi Aqil Bin Zulkifli', 'hilmi@polibest.test', 'member'],
        ])->map(fn (array $data) => User::updateOrCreate(['email' => $data[1]], [
            'name' => $data[0],
            'password' => Hash::make('password'),
            'role' => $data[2],
            'department' => 'JTMK',
            'membership_status' => 'active',
            'joined_date' => now()->toDateString(),
            'fee_balance' => $data[2] === 'member' ? 20 : 0,
        ]));

        $activity = Activity::firstOrCreate(['title' => 'Mesyuarat Agung Tahunan PoliBest'], [
            'description' => 'Program tahunan kelab staf Politeknik Besut.',
            'date_time' => now()->addWeek(),
            'location' => 'Dewan Seminar Politeknik Besut',
            'max_participants' => 80,
            'status' => 'approved',
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        foreach ($users as $user) {
            PortalNotification::firstOrCreate([
                'user_id' => $user->id,
                'title' => 'Selamat datang ke PoliBest',
            ], [
                'message' => 'Sistem Pengurusan Kelab Staf telah sedia digunakan.',
                'type' => 'info',
                'link' => route('activities.show', $activity),
            ]);
        }
    }
}
