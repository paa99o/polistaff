<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Activity;
use App\Models\PolimartItem;
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
            ['Admin PoliBest 2', 'admin2@polibest.test', 'admin'],
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

        $seller = $users->firstWhere('role', 'member') ?? $users->first();

        foreach ([
            [
                'name' => 'Brownies Kedut Homemade',
                'category' => 'Makanan',
                'price' => 18.00,
                'stock' => 10,
                'description' => 'Brownies coklat fudgy, sesuai untuk minum petang atau hadiah. Satu bekas 9 potong.',
                'contact' => '012-345 6789',
            ],
            [
                'name' => 'Kemeja Batik Lelaki',
                'category' => 'Pakaian',
                'price' => 35.00,
                'stock' => 5,
                'description' => 'Kemeja batik saiz L, dipakai sekali sahaja dan masih dalam keadaan sangat baik.',
                'contact' => '013-456 7890',
            ],
            [
                'name' => 'Servis Design Poster Program',
                'category' => 'Servis',
                'price' => 25.00,
                'stock' => 8,
                'description' => 'Design poster digital untuk program kelab, hebahan rasmi atau media sosial.',
                'contact' => '014-567 8901',
            ],
            [
                'name' => 'Pokok Hiasan Dalam Pasu',
                'category' => 'Rumah & Taman',
                'price' => 15.00,
                'stock' => 4,
                'description' => 'Pokok hiasan mudah dijaga dalam pasu kecil. Sesuai untuk meja pejabat.',
                'contact' => '016-678 9012',
            ],
            [
                'name' => 'Beg Galas Laptop Pre-loved',
                'category' => 'Barangan Terpakai',
                'price' => 40.00,
                'stock' => 2,
                'description' => 'Beg galas berkusyen untuk laptop sehingga 15 inci, bersih dan masih kukuh.',
                'contact' => '017-789 0123',
            ],
            [
                'name' => 'Kek Pisang Walnut',
                'category' => 'Makanan',
                'price' => 22.00,
                'stock' => 6,
                'description' => 'Kek pisang lembut dengan walnut rangup. Tempahan perlu dibuat sehari awal.',
                'contact' => '018-890 1234',
            ],
        ] as $item) {
            PolimartItem::updateOrCreate(
                ['user_id' => $seller->id, 'name' => $item['name']],
                [...$item, 'user_id' => $seller->id, 'status' => 'active'],
            );
        }

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
