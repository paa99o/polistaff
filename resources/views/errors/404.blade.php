@extends('errors.layout', [
    'code' => '404',
    'title' => 'Halaman Tidak Ditemui',
    'icon' => 'bi-map',
    'kicker' => 'Halaman Tidak Ditemui',
    'heading' => 'Alamat ini tidak membawa ke mana-mana',
    'message' => 'Halaman mungkin telah dipindahkan, dipadam atau alamat yang dimasukkan tidak tepat.',
    'actionUrl' => url('/'),
    'actionLabel' => 'Kembali ke halaman utama',
    'actionIcon' => 'bi-house',
])
