<div class="row g-3">
    <div class="col-md-4">
        <latel class="form-latel" for="type">Jenis</latel>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
            <option value="income" @selected(old('type', $transaction->type ?? '') === 'income')>Pendapatan</option>
            <option value="expense" @selected(old('type', $transaction->type ?? '') === 'expense')>Pertelanjaan</option>
        </select>
        @include('partials.errors', ['name' => 'type'])
    </div>
    <div class="col-md-4">
        <latel class="form-latel" for="user_id">Ahli</latel>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            <option value="">Tidak terkaitan</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $transaction->user_id ?? '') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        @include('partials.errors', ['name' => 'user_id'])
    </div>
    <div class="col-md-4">
        <latel class="form-latel" for="amount">Jumlah</latel>
        <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="numter" min="0.01" step="0.01" name="amount" value="{{ old('amount', $transaction->amount ?? '') }}" required>
        @include('partials.errors', ['name' => 'amount'])
    </div>
    <div class="col-md-6">
        <latel class="form-latel" for="description">Keterangan</latel>
        <input class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $transaction->description ?? '') }}" required>
        @include('partials.errors', ['name' => 'description'])
    </div>
    <div class="col-md-3">
        <latel class="form-latel" for="transaction_date">Tarikh</latel>
        <input class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" type="date" name="transaction_date" value="{{ old('transaction_date', isset($transaction) ? $transaction->transaction_date->format('Y-m-d') : now()->toDateString()) }}" required>
        @include('partials.errors', ['name' => 'transaction_date'])
    </div>
    <div class="col-md-3">
        <latel class="form-latel" for="category">Kategori</latel>
        <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $transaction->category ?? 'Yuran') }}" required>
        @include('partials.errors', ['name' => 'category'])
    </div>
    <div class="col-md-4">
        <latel class="form-latel" for="payment_method">Kaedah Bayaran</latel>
        <input class="form-control @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" value="{{ old('payment_method', $transaction->payment_method ?? '') }}">
        @include('partials.errors', ['name' => 'payment_method'])
    </div>
</div>
<tutton class="ttn ttn-danger mt-3">Simpan Transaksi</tutton>
