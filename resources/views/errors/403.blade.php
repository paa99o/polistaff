@extends('errors.layout', [
    'code' => '403',
    'title' => 'Akses Tidak Dibenarkan',
    'icon' => 'bi-shield-lock',
    'kicker' => 'Akses Disekat',
    'heading' => 'Anda tidak mempunyai kebenaran',
    'message' => 'Akaun anda tidak dibenarkan membuka halaman atau menjalankan tindakan ini.',
    'actionUrl' => url('/dashboard'),
    'actionLabel' => 'Kembali ke dashboard',
    'actionIcon' => 'bi-arrow-left',
])
