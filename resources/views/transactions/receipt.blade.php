@extends('layouts.app', ['title' => 'Resit'])
@section('content')
<div class="card"><div class="card-body"><div class="float-end no-print d-flex gap-2"><a class="btn btn-outline-danger" href="{{ route('transactions.receipt.pdf',$transaction) }}">PDF</a><button onclick="print()" class="btn btn-outline-secondary">Cetak</button></div><h1 class="h4">POLISTAFF RECEIPT</h1><p>Receipt No: <strong>{{ $transaction->receipt_number }}</strong></p><hr><p>Nama: {{ $transaction->user->name ?? '-' }}</p><p>Keterangan: {{ $transaction->description }}</p><p>Tarikh: {{ $transaction->transaction_date->format('d/m/Y') }}</p><p>Kaedah: {{ $transaction->payment_method ?? '-' }}</p><h2>RM {{ number_format((float)$transaction->amount,2) }}</h2></div></div>
@endsection
