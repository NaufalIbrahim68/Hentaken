<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Henkaten Man Power</title>
    
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #777;
            padding: 5px;
            text-align: left;
            word-wrap: break-word; 
            /* Menjaga perataan vertikal jika ada gambar */
            vertical-align: middle; 
        }
        th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }
        .header-table {
            margin-bottom: 20px;
            border: none;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .logo {
            width: 100px;
        }
        .title {
            text-align: right;
        }
        h1 {
            font-size: 18px;
            margin: 0;
        }
        h2 {
            font-size: 14px;
            margin: 5px 0;
            color: #555;
        }
        .filter-info {
            font-size: 11px;
            margin-bottom: 15px;
        }
        .text-center {
            text-align: center;
        }

        /* 1. TAMBAHAN: CSS UNTUK GAMBAR LAMPIRAN */
        .lampiran-img {
            width: 70px; /* Sesuaikan ukuran gambar */
            height: auto;
            border: 1px solid #ccc;
            padding: 2px;
            border-radius: 3px;
        }
    </style>
</head>
<body>

    {{-- HEADER: LOGO DAN JUDUL --}}
    <table class="header-table">
        <tr>
            <td>
                <img src="{{ public_path('assets/images/AVI.png') }}" alt="Logo" class="logo">
            </td>
            <td class="title">
                <h1>Laporan Activity Log</h1>
                <h2>Henkaten Man Power</h2>
            </td>
        </tr>
    </table>

    {{-- INFORMASI FILTER (DARI CONTROLLER) --}}
    <div class="filter-info">
        @if(isset($filterDate) && $filterDate)
            <strong>Tanggal Filter:</strong> {{ \Carbon\Carbon::parse($filterDate)->format('d M Y') }}<br>
        @endif
        @if(isset($filterLine) && $filterLine)
            <strong>Line Area:</strong> {{ $filterLine }}
        @endif
    </div>

    {{-- TABEL DATA UTAMA --}}
    <table>
        <thead>
            <tr>
                <th>Tgl Dibuat</th>
                <th>Line Area</th>
                <th>Grup</th>
                <th>Nama Sebelum</th>
                <th>Nama Sesudah</th>
                <th>Station</th>
                <th>Tgl Efektif</th>
                <th>Waktu</th>
                <th>Keterangan</th>
                <th>Status</th>
                <th>Lampiran</th> {{-- 2. TAMBAHAN: Header kolom baru --}}
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="text-center">{{ $log->created_at ? $log->created_at->format('d M Y') : '-' }}</td>
                    <td class="text-center">{{ $log->line_area ?? '-' }}</td>
                    <td class="text-center">{{ $log->grup ?? '-' }}</td>
                    <td class="text-center">{{ $log->nama ?? '-' }}</td>
                    <td class="text-center">{{ $log->nama_after ?? '-' }}</td>
                    <td class="text-center">{{ $log->station->station_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $log->effective_date ? \Carbon\Carbon::parse($log->effective_date)->format('d M Y') : '-' }}</td>
                    <td class="text-center">
                        {{ $log->time_start ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '-' }}
                        -
                        {{ $log->time_end ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '-' }}
                    </td>
                    <td style="width: 15%; text-center">{{ $log->keterangan ?? '-' }}</td>
                    <td class="text-center">{{ $log->status ?? '-' }}</td>

                    {{-- 3. TAMBAHAN: Sel data baru untuk Lampiran --}}
                    <td class="text-center">
                        {{-- 
                            Cek apakah file ada di storage. Ini mencegah error 'broken image'.
                            Path ini mengasumsikan Anda sudah menjalankan 'php artisan storage:link'
                        --}}
                        @if ($log->lampiran && file_exists(public_path('storage/' . $log->lampiran)))
                            <img src="{{ public_path('storage/' . $log->lampiran) }}" alt="Lampiran" class="lampiran-img">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    {{-- 4. PERBARUI: Colspan diubah dari 11 menjadi 12 --}}
                    <td colspan="12" class="text-center">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>