@extends('layouts.app', ['title' => 'Aktiviti Baru'])
@section('content')
<div class="card"><div class="card-body"><h1 class="h4 mb-3">Aktiviti Baru</h1><form method="post" action="{{ route('activities.store') }}">@csrf @include('activities._form')</form></div></div>
@endsection
