    <!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reminder Henkaten Pending</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .content {
            background: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
        }

        .summary-box {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #e9ecef;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .footer {
            background: #e9ecef;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
            color: #6c757d;
        }

        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2 style="margin: 0;">⚠️ Reminder Henkaten Pending</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">Lebih dari 7 hari menunggu approval</p>
    </div>

    <div class="content">
        <p>Halo <strong>{{ $name }}</strong>,</p>

        <div class="warning">
            <strong>Perhatian:</strong> Ada <strong>{{ $total }}</strong> data Henkaten yang masih
            <strong>Pending</strong> lebih dari 7 hari dan membutuhkan approval Anda.
        </div>

        <div class="summary-box">
            <h4 style="margin: 0 0 10px 0;">📊 Ringkasan per Tipe:</h4>
            @foreach ($summary as $type => $count)
                <div class="summary-item">
                    <span>{{ $type }}</span>
                    <span class="badge">{{ $count }}</span>
                </div>
            @endforeach
        </div>

        <h4>📋 Detail Data Pending:</h4>
        <table>
            <thead>
                <tr>
                    <th>Tipe</th>
                    <th>Nama dan NPK</th>
                    <th>Line Area</th>
                    <th>Tanggal Dibuat</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item['type'] }}</td>
                        <td>{{ $item['npk'] }}</td>
                        <td>{{ $item['line_area'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($item['created_at'])->format('d M Y') }}</td>
                        <td>{{ $item['keterangan'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

       
    </div>

    <div class="footer">
        <p>Email ini dikirim otomatis oleh sistem Henkaten Management.</p>
        <p>PT Astra Visteon Indonesia</p>
    </div>
</body>

</html>
