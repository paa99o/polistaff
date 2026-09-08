@extends('errors.layout', [
    'code' => '500',
    'title' => 'Ralat Sistem',
    'icon' => 'bi-exclamation-octagon',
    'kicker' => 'Gangguan Sistem',
    'heading' => 'Sesuatu tidak berjalan seperti sepatutnya',
    'message' => 'Permintaan anda tidak dapat diselesaikan buat masa ini. Data teknikal ralat telah disimpan untuk semakan.',
    'actionUrl' => url()->current(),
    'actionLabel' => 'Cuba semula',
    'actionIcon' => 'bi-arrow-clockwise',
])
