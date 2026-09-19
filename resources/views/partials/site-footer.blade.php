<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-brand">
            <a href="{{ url('/') }}" aria-label="Halaman utama POLIBEST">
                <img src="{{ asset('images/polibest-logo.png') }}" alt="Logo Kelab POLIBEST">
            </a>
            <div>
                <strong>POLIBEST</strong>
                <p>Portal Pengurusan Kelab Staf</p>
            </div>
        </div>

        <div class="site-footer-main">
            <nav class="site-footer-links" aria-label="Pautan kaki halaman">
                <a href="{{ url('/') }}">Laman Utama</a>
                <a href="{{ route('activities.index') }}">Aktiviti</a>
                <a href="{{ route('polimart.index') }}">PoliMart</a>
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <a href="{{ route('profile.show') }}">Profil</a>
                @else
                    <a href="{{ route('login') }}">Log Masuk</a>
                    <a href="{{ route('register') }}">Daftar</a>
                @endauth
                <a href="mailto:admin@polibest.local">Hubungi Kami</a>
            </nav>
            <div class="site-footer-social" aria-label="Pautan media sosial">
                <a href="mailto:admin@polibest.local" aria-label="E-mel POLIBEST"><i class="bi bi-envelope" aria-hidden="true"></i></a>
                <a href="{{ route('polimart.index') }}" aria-label="Kedai PoliMart"><i class="bi bi-bag" aria-hidden="true"></i></a>
                <a href="{{ route('activities.index') }}" aria-label="Aktiviti POLIBEST"><i class="bi bi-calendar3" aria-hidden="true"></i></a>
            </div>
            <div class="site-footer-bottom">
                <span>© {{ date('Y') }} POLIBEST. Hak Cipta Terpelihara.</span>
                <span>Portal Pengurusan Kelab Staf</span>
            </div>
        </div>
    </div>
</footer>
