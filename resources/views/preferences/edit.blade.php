@extends('layouts.app')

@section('content')
<section class="personal-settings-page">
    <div class="settings-heading">
        <div>
            <span class="section-kicker">Akaun Saya</span>
            <h1>Tetapan Saya</h1>
            <p>Urus cara Polistaff berhubung dan dipaparkan untuk akaun anda.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('profile.show') }}">
            <i class="bi bi-person me-2" aria-hidden="true"></i>Profil Saya
        </a>
    </div>

    <form method="post" action="{{ route('preferences.update') }}">
        @csrf
        @method('put')

        <div class="settings-layout">
            <nav class="settings-section-nav" aria-label="Bahagian tetapan">
                <a href="#notifications"><i class="bi bi-bell" aria-hidden="true"></i><span>Notifikasi Emel</span></a>
                <a href="#appearance"><i class="bi bi-circle-half" aria-hidden="true"></i><span>Paparan</span></a>
                <a href="#accessibility"><i class="bi bi-universal-access" aria-hidden="true"></i><span>Aksesibiliti</span></a>
                <a href="#security"><i class="bi bi-shield-lock" aria-hidden="true"></i><span>Keselamatan</span></a>
            </nav>

            <div class="settings-content">
                <section class="card settings-panel" id="notifications">
                    <div class="card-body">
                        <div class="settings-panel-heading">
                            <div class="settings-panel-icon"><i class="bi bi-envelope" aria-hidden="true"></i></div>
                            <div>
                                <h2>Notifikasi Emel</h2>
                                <p>Pilih emel tambahan yang anda mahu terima. Notifikasi penting dalam aplikasi masih akan dipaparkan.</p>
                            </div>
                        </div>

                        <div class="preference-list">
                            <label class="preference-row" for="email_announcements">
                                <span><strong>Hebahan dan pengumuman</strong><small>Kenyataan rasmi atau makluman yang dihantar oleh pengurusan.</small></span>
                                <span class="form-check form-switch"><input type="hidden" name="email_announcements" value="0"><input class="form-check-input" id="email_announcements" type="checkbox" name="email_announcements" value="1" @checked(old('email_announcements', $user->email_announcements))></span>
                            </label>
                            <label class="preference-row" for="email_activities">
                                <span><strong>Aktiviti kelab</strong><small>Aktiviti dibuka, diluluskan atau dibatalkan oleh pengurusan.</small></span>
                                <span class="form-check form-switch"><input type="hidden" name="email_activities" value="0"><input class="form-check-input" id="email_activities" type="checkbox" name="email_activities" value="1" @checked(old('email_activities', $user->email_activities))></span>
                            </label>
                            <label class="preference-row" for="email_finance">
                                <span><strong>Bayaran dan tuntutan</strong><small>Keputusan semakan bayaran, resit dan status tuntutan anda.</small></span>
                                <span class="form-check form-switch"><input type="hidden" name="email_finance" value="0"><input class="form-check-input" id="email_finance" type="checkbox" name="email_finance" value="1" @checked(old('email_finance', $user->email_finance))></span>
                            </label>
                            <label class="preference-row" for="email_fee_reminders">
                                <span><strong>Peringatan yuran</strong><small>Peringatan automatik apabila akaun masih mempunyai baki yuran.</small></span>
                                <span class="form-check form-switch"><input type="hidden" name="email_fee_reminders" value="0"><input class="form-check-input" id="email_fee_reminders" type="checkbox" name="email_fee_reminders" value="1" @checked(old('email_fee_reminders', $user->email_fee_reminders))></span>
                            </label>
                        </div>
                    </div>
                </section>

                <section class="card settings-panel" id="appearance">
                    <div class="card-body">
                        <div class="settings-panel-heading">
                            <div class="settings-panel-icon"><i class="bi bi-palette" aria-hidden="true"></i></div>
                            <div><h2>Paparan</h2><p>Pilih tema yang selesa digunakan pada peranti anda.</p></div>
                        </div>
                        <div class="choice-grid">
                            @foreach(['light' => ['bi-sun', 'Cerah', 'Paparan terang untuk kegunaan harian.'], 'dark' => ['bi-moon-stars', 'Gelap', 'Kurangkan silau dalam persekitaran gelap.']] as $value => [$icon, $label, $description])
                                <label class="preference-choice">
                                    <input type="radio" name="theme_preference" value="{{ $value }}" @checked(old('theme_preference', $user->theme_preference) === $value)>
                                    <span><i class="bi {{ $icon }}" aria-hidden="true"></i><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                                </label>
                            @endforeach
                        </div>
                        @include('partials.errors', ['name' => 'theme_preference'])
                    </div>
                </section>

                <section class="card settings-panel" id="accessibility">
                    <div class="card-body">
                        <div class="settings-panel-heading">
                            <div class="settings-panel-icon"><i class="bi bi-universal-access" aria-hidden="true"></i></div>
                            <div><h2>Aksesibiliti</h2><p>Laraskan kebolehbacaan dan pergerakan antara muka.</p></div>
                        </div>
                        <fieldset>
                            <legend class="settings-field-label">Saiz teks</legend>
                            <div class="choice-grid">
                                <label class="preference-choice">
                                    <input type="radio" name="text_size_preference" value="normal" @checked(old('text_size_preference', $user->text_size_preference) === 'normal')>
                                    <span><i class="bi bi-fonts" aria-hidden="true"></i><strong>Standard</strong><small>Saiz asal Polistaff.</small></span>
                                </label>
                                <label class="preference-choice">
                                    <input type="radio" name="text_size_preference" value="large" @checked(old('text_size_preference', $user->text_size_preference) === 'large')>
                                    <span><i class="bi bi-type-h1" aria-hidden="true"></i><strong>Lebih Besar</strong><small>Teks lebih mudah dibaca.</small></span>
                                </label>
                            </div>
                        </fieldset>
                        <label class="preference-row preference-row-standalone" for="reduce_motion">
                            <span><strong>Kurangkan animasi</strong><small>Kurangkan gerakan ketika halaman dan elemen dipaparkan.</small></span>
                            <span class="form-check form-switch"><input type="hidden" name="reduce_motion" value="0"><input class="form-check-input" id="reduce_motion" type="checkbox" name="reduce_motion" value="1" @checked(old('reduce_motion', $user->reduce_motion))></span>
                        </label>
                    </div>
                </section>

                <section class="card settings-panel" id="security">
                    <div class="card-body settings-security-row">
                        <div class="settings-panel-heading mb-0">
                            <div class="settings-panel-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
                            <div><h2>Keselamatan Akaun</h2><p>Kemas kini kata laluan anda secara berkala untuk melindungi akaun.</p></div>
                        </div>
                        <a class="btn btn-outline-danger" href="{{ route('profile.password') }}">Tukar Kata Laluan</a>
                    </div>
                </section>

                <div class="settings-savebar">
                    <span><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Tetapan ini hanya digunakan untuk akaun anda.</span>
                    <button class="btn btn-danger" type="submit"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Simpan Tetapan</button>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection
