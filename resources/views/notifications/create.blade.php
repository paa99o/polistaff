@extends('layouts.app', ['title' => 'Hantar Notifikasi'])

@section('content')
<div class="card">
    <div class="card-body">
        <h1 class="h4">Hantar Notifikasi</h1>
        <form method="post" action="{{ route('notifications.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="target">Sasaran</label>
                    <select class="form-select @error('target') is-invalid @enderror" id="target" name="target">
                        <option value="all_active" @selected(old('target', 'all_active') === 'all_active')>Semua ahli aktif</option>
                        <option value="department" @selected(old('target') === 'department')>Department tertentu</option>
                        <option value="role" @selected(old('target') === 'role')>Role tertentu</option>
                        <option value="individual" @selected(old('target') === 'individual')>Individu tertentu</option>
                    </select>
                    @include('partials.errors', ['name' => 'target'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="type">Jenis</label>
                    <input class="form-control @error('type') is-invalid @enderror" id="type" name="type" value="{{ old('type', 'info') }}" required>
                    @include('partials.errors', ['name' => 'type'])
                </div>
                <div class="col-md-4" id="department-filter">
                    <label class="form-label" for="department">Department</label>
                    <select class="form-select @error('department') is-invalid @enderror" id="department" name="department">
                        <option value="">Pilih department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                        @endforeach
                    </select>
                    @include('partials.errors', ['name' => 'department'])
                </div>
                <div class="col-md-4" id="role-filter">
                    <label class="form-label" for="role">Role</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                        <option value="">Pilih role</option>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @include('partials.errors', ['name' => 'role'])
                </div>
                <div class="col-md-4" id="user-filter">
                    <label class="form-label" for="user_id">Ahli</label>
                    <input class="form-control mb-2" id="user-search" type="search" placeholder="Cari nama atau emel ahli" autocomplete="off">
                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                        <option value="">Pilih ahli</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" data-department="{{ $user->department }}" data-role="{{ $user->role }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                        @endforeach
                    </select>
                    @include('partials.errors', ['name' => 'user_id'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="title">Tajuk</label>
                    <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @include('partials.errors', ['name' => 'title'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="link">Link</label>
                    <input class="form-control @error('link') is-invalid @enderror" id="link" name="link" value="{{ old('link') }}">
                    @include('partials.errors', ['name' => 'link'])
                </div>
                <div class="col-12">
                    <label class="form-label" for="message">Mesej</label>
                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="4" required>{{ old('message') }}</textarea>
                    @include('partials.errors', ['name' => 'message'])
                </div>
            </div>
            <button class="btn btn-danger mt-3">Hantar</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const target = document.getElementById('target');
        const department = document.getElementById('department');
        const role = document.getElementById('role');
        const user = document.getElementById('user_id');
        const userSearch = document.getElementById('user-search');
        const groups = {
            department: document.getElementById('department-filter'),
            role: document.getElementById('role-filter'),
            individual: document.getElementById('user-filter'),
        };

        const updateUserOptions = () => {
            const query = userSearch.value.trim().toLowerCase();
            [...user.options].forEach((option, index) => {
                if (index === 0) return;

                const matchesDepartment = !department.value || option.dataset.department === department.value;
                const matchesRole = !role.value || option.dataset.role === role.value;
                const matchesSearch = !query || option.text.toLowerCase().includes(query);
                option.hidden = !(matchesDepartment && matchesRole && matchesSearch);

                if (option.selected && option.hidden) {
                    user.value = '';
                }
            });
        };

        const updateTargetFields = () => {
            const selectedTarget = target.value;
            const visibleFields = {
                department: selectedTarget === 'department' || selectedTarget === 'individual',
                role: selectedTarget === 'role' || selectedTarget === 'individual',
                individual: selectedTarget === 'individual',
            };

            Object.entries(groups).forEach(([field, group]) => {
                group.hidden = !visibleFields[field];
            });

            department.disabled = !visibleFields.department;
            role.disabled = !visibleFields.role;
            user.disabled = !visibleFields.individual;
            userSearch.disabled = !visibleFields.individual;

            if (!visibleFields.department) department.value = '';
            if (!visibleFields.role) role.value = '';
            if (!visibleFields.individual) {
                user.value = '';
                userSearch.value = '';
            }

            updateUserOptions();
        };

        target.addEventListener('change', updateTargetFields);
        department.addEventListener('change', updateUserOptions);
        role.addEventListener('change', updateUserOptions);
        userSearch.addEventListener('input', updateUserOptions);
        updateTargetFields();
    })();
</script>
@endpush
