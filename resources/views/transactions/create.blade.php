@extends('layouts.app', ['title' => 'Transaksi Baru'])
@section('content')<div class="card"><div class="card-body"><h1 class="h4">Transaksi Baru</h1><form method="post" action="{{ route('transactions.store') }}">@csrf @include('transactions._form')</form></div></div>@endsection
