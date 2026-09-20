@extends('layouts.app', ['title' => 'Aktiviti Baru'])
@section('content')
<div class="card"><div class="card-body"><h1 class="h4 mb-3">Cipta Aktiviti</h1><p class="text-muted">Permohonan anda akan dihantar kepada bendahari untuk kelulusan.</p><form method="post" action="{{ route('activities.store') }}" enctype="multipart/form-data">@csrf @include('activities._form', ['isCreate' => true])</form></div></div>
@endsection
