<?php

namespace App\Support;

class PolistaffLabels
{
    public static function status(?string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'pending' => 'Menunggu',
            'draft' => 'Draf',
            'pending_approval' => 'Menunggu Kelulusan',
            'approved' => 'Diluluskan',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            'registered' => 'Berdaftar',
            'waitlisted' => 'Senarai Menunggu',
            'treasurer_verified' => 'Disahkan Bendahari',
            'reversed' => 'Dibatalkan',
            default => $status ? str($status)->replace('_', ' ')->title()->toString() : '-',
        };
    }

    public static function statusClass(?string $status): string
    {
        return match ($status) {
            'active', 'approved', 'registered', 'treasurer_verified' => 'text-bg-success',
            'pending', 'pending_approval', 'waitlisted', 'draft' => 'text-bg-warning',
            'rejected', 'cancelled', 'inactive', 'reversed' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    }

    public static function role(?string $role): string
    {
        return match ($role) {
            'member' => 'Ahli',
            'treasurer' => 'Bendahari',
            'chairman' => 'Pengerusi',
            'admin' => 'Admin',
            default => $role ? str($role)->replace('_', ' ')->title()->toString() : '-',
        };
    }
}
