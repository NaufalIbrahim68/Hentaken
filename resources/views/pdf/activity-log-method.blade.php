<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Henkaten Method</title>
    
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
                {{-- Gunakan public_path() untuk PDF --}}
                <img src="{{ public_path('assets/images/AVI.png') }}" alt="Logo" class="logo">
            </td>
            <td class="title">
                <h1>Laporan Activity Log</h1>
                {{-- UBAH: Judul --}}
                <h2>Henkaten Method</h2>
            </td>
        </tr>
    </table>

    {{-- INFORMASI FILTER --}}
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
                {{-- UBAH: Kolom disesuaikan untuk Method --}}
                <th>Tgl Dibuat</th>
                <th>Line Area</th>
                <th>Station</th>
                <th>Ket. Sebelum</th>
                <th>Ket. Sesudah</th>
                <th>Tgl Efektif</th>
                <th>Waktu</th>
                <th>Status</th>
                <th>Lampiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    {{-- created_at --}}
                    <td>{{ $log->created_at ? $log->created_at->format('d M Y') : '-' }}</td>
                    
                    {{-- line_area (dari skema methods_henkaten) --}}
                    <td>{{ $log->line_area ?? '-' }}</td>
                    
                    {{-- station_id (via relasi) --}}
                    <td>{{ $log->station->station_name ?? 'N/A' }}</td>
                    
                    {{-- keterangan --}}
                    <td style="width: 15%;">{{ $log->keterangan ?? '-' }}</td>
                    
                    {{-- keterangan_after --}}
                    <td style="width: 15%;">{{ $log->keterangan_after ?? '-' }}</td>
                    
                    {{-- effective_date --}}
                    <td>{{ $log->effective_date ? \Carbon\Carbon::parse($log->effective_date)->format('d M Y') : '-' }}</td>
                    
                    {{-- time_start & time_end --}}
                    <td>
                        {{ $log->time_start ? \Carbon\Carbon::parse($log->time_start)->format('H:i') : '-' }}
                        -
                        {{ $log->time_end ? \Carbon\Carbon::parse($log->time_end)->format('H:i') : '-' }}
                    </td>
                    
                    
                    
                    {{-- status --}}
                    <td class="text-center">{{ $log->status ?? '-' }}</td>

                    {{-- lampiran --}}
                    <td class="text-center">
                        @if ($log->lampiran && file_exists(public_path('storage/' . $log->lampiran)))
                            <img src="{{ public_path('storage/' . $log->lampiran) }}" alt="Lampiran" class="lampiran-img">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    {{-- UBAH: Colspan disesuaikan (10 kolom) --}}
                    <td colspan="10" class="text-center">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>