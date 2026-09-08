<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="type">Jenis</label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
            <option value="income" @selected(old('type', $transaction->type ?? '') === 'income')>Pendapatan</option>
            <option value="expense" @selected(old('type', $transaction->type ?? '') === 'expense')>Perbelanjaan</option>
        </select>
        @include('partials.errors', ['name' => 'type'])
    </div>
    <div class="col-md-4">
        <label class="form-label" for="user_id">Ahli</label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            <option value="">Tidak berkaitan</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $transaction->user_id ?? '') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        @include('partials.errors', ['name' => 'user_id'])
    </div>
    <div class="col-md-4">
        <label class="form-label" for="amount">Jumlah</label>
        <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount', $transaction->amount ?? '') }}" required>
        @include('partials.errors', ['name' => 'amount'])
    </div>
    <div class="col-md-6">
        <label class="form-label" for="description">Keterangan</label>
        <input class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $transaction->description ?? '') }}" required>
        @include('partials.errors', ['name' => 'description'])
    </div>
    <div class="col-md-3">
        <label class="form-label" for="transaction_date">Tarikh</label>
        <input class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" type="date" name="transaction_date" value="{{ old('transaction_date', isset($transaction) ? $transaction->transaction_date->format('Y-m-d') : now()->toDateString()) }}" required>
        @include('partials.errors', ['name' => 'transaction_date'])
    </div>
    <div class="col-md-3">
        <label class="form-label" for="category">Kategori</label>
        <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $transaction->category ?? 'Yuran') }}" required>
        @include('partials.errors', ['name' => 'category'])
    </div>
    <div class="col-md-4">
        <label class="form-label" for="payment_method">Kaedah Bayaran</label>
        <input class="form-control @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" value="{{ old('payment_method', $transaction->payment_method ?? '') }}">
        @include('partials.errors', ['name' => 'payment_method'])
    </div>
</div>
<button class="btn btn-danger mt-3">Simpan Transaksi</button>
