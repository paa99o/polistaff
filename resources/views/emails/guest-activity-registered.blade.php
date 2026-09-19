@component('mail::message')
# Pendaftaran aktiviti diterima

Hai {{ $registration->name }},

Pendaftaran anda untuk aktiviti **{{ $registration->activity->title }}** telah diterima.

**Tarikh:** {{ $registration->activity->date_time->format('d/m/Y h:i A') }}  
**Lokasi:** {{ $registration->activity->location }}  
**Status:** {{ $registration->status === 'waitlisted' ? 'Senarai menunggu' : 'Berdaftar' }}

Terima kasih kerana menyertai program POLIBEST.

Terima kasih,  
POLIBEST
@endcomponent
