@extends('layouts.app', ['title' => 'Edit Aktiviti'])
@section('content')
<div class="card"><div class="card-body"><h1 class="h4 mb-3">Edit Aktiviti</h1><form method="post" action="{{ route('activities.update',$activity) }}" enctype="multipart/form-data">@csrf @method('put') @include('activities._form')</form></div></div>
@endsection
