@php
$nav = [
    ['r'=>'dashboard',          'i'=>'bi-speedometer2',    'l'=>'Dashboard'],
    ['r'=>'pob-entries.index',  'i'=>'bi-journal-text',    'l'=>'Laporan POB'],
    ['r'=>'employees.index',    'i'=>'bi-people',          'l'=>'Data Karyawan'],
    ['r'=>'companies.index',    'i'=>'bi-building',        'l'=>'Perusahaan'],
    ['r'=>'notifications.index','i'=>'bi-bell',            'l'=>'Notifikasi WA'],
    ['r'=>'report.index',       'i'=>'bi-bar-chart-line',  'l'=>'Laporan'],
    ['r'=>'users.index',        'i'=>'bi-person-gear',     'l'=>'Manajemen User'],
    ['r'=>'dashboard.import',   'i'=>'bi-upload',          'l'=>'Import Excel'],
];
@endphp
@foreach($nav as $n)
<a href="{{ route($n['r']) }}" class="{{ request()->routeIs($n['r'].'*') ? 'on' : '' }}">
    <i class="bi {{ $n['i'] }}"></i>{{ $n['l'] }}
</a>
@endforeach
