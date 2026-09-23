<div class="dropdown">
    <button class="btn {{ $buttonClass ?? 'btn-outline-danger' }} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-download me-2" aria-hidden="true"></i>{{ $label ?? 'Eksport Laporan' }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach($options as $option)
            <li>
                @if(isset($option['onclick']))
                    <button class="dropdown-item" type="button" onclick="{{ $option['onclick'] }}">
                        <i class="bi {{ $option['icon'] ?? 'bi-file-earmark' }} me-2" aria-hidden="true"></i>{{ $option['label'] }}
                    </button>
                @else
                    <a class="dropdown-item" href="{{ $option['url'] }}">
                        <i class="bi {{ $option['icon'] ?? 'bi-file-earmark' }} me-2" aria-hidden="true"></i>{{ $option['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
