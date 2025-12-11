<p>Halo {{ $name }},</p>

<p>Ada <strong>{{ $total }}</strong> data <strong>Method Henkaten</strong> yang masih Pending lebih dari 7 hari.</p>

<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 13px;">
    <thead style="background:#f3f3f3;">
        <tr>
            <th>Keterangan Awal</th>
            <th>Keterangan Sesudah</th>
            <th>Line Area</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($items as $item)
        <tr>
            <td>{{ $item->keterangan ?? '-' }}</td>
            <td>{{ $item->keterangan_after ?? '-' }}</td>
            <td>{{ $item->line_area ?? '-' }}</td>
            <td><strong style="color:#b58900;">{{ $item->status }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top:14px;">Mohon segera melakukan approval pada sistem.</p>
<p>Terima kasih.</p>
