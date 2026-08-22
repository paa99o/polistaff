@extends('layouts.app', ['title' => 'Edit Transaksi'])
@section('content')<div class="card"><div class="card-body"><h1 class="h4">Edit Transaksi</h1><form method="post" action="{{ route('transactions.update',$transaction) }}">@csrf @method('put') @include('transactions._form')</form></div></div>@endsection
