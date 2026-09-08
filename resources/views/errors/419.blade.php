@extends('errors.layout', [
    'code' => '419',
    'title' => 'Sesi Telah Tamat',
    'icon' => 'bi-hourglass-split',
    'kicker' => 'Sesi Tamat',
    'heading' => 'Sesi keselamatan anda telah tamat',
    'message' => 'Ini boleh berlaku apabila halaman dibiarkan terbuka terlalu lama. Log masuk semula sebelum meneruskan.',
    'actionUrl' => url('/login'),
    'actionLabel' => 'Log masuk semula',
    'actionIcon' => 'bi-box-arrow-in-right',
])
