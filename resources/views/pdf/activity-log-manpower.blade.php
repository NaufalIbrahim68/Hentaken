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
        .lampiran-img {
            width: 70px; 
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
            <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($filterDate)->format('d M Y') }}<br>
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
                <th>Supp. Part No. Start</th>
                <th>Supp. Part No. End</th>
                <th>Keterangan</th>
                <th>Status</th>
                <th>Lampiran</th> 
                {{-- 1. TAMBAHAN HEADER NOTE --}}
                <th>Note</th>
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
                        <td class="text-center">{{ $log->serial_number_start ?? '-' }}</td>
                        <td class="text-center">{{ $log->serial_number_end ?? '-' }}</td>
                    <td style="text-center">{{ $log->keterangan ?? '-' }}</td>
                    <td class="text-center">{{ $log->status ?? '-' }}</td>
                    <td class="text-center">
    @if ($log->lampiran && file_exists(public_path('storage/' . $log->lampiran)))
        @php
            $extension = strtolower(pathinfo($log->lampiran, PATHINFO_EXTENSION));
        @endphp

        {{-- Jika lampiran berupa gambar --}}
        @if (in_array($extension, ['jpg', 'jpeg', 'png']))
            <img src="{{ public_path('storage/' . $log->lampiran) }}" alt="Lampiran" class="lampiran-img">

        {{-- Jika lampiran berupa zip/rar atau file lain --}}
        @else
            <span>Lampiran tersedia:</span>
            <br>
            <span style="font-size: 12px;">
                {{ asset('storage/' . $log->lampiran) }}
            </span>
        @endif
    @else
        -
    @endif
</td>

                    <td class="text-center">
                        @if ($log->status == 'APPROVED')
                            {{ $log->note ?? '-' }}
                        @else
                            -
                        @endif
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>