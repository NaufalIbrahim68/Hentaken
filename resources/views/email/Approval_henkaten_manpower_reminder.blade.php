<p>Halo {{ $name }},</p>

<p>
    Ada <strong>{{ $total }}</strong> data Henkaten yang masih
    <strong>Pending</strong> lebih dari 7 hari dan membutuhkan approval Anda.
</p>

<p><strong>Rincian Data:</strong></p>

<ul>
    @foreach ($items as $item)
        <li style="margin-bottom: 12px;">
            <strong>ID:</strong> {{ $item->id }} <br>
            <strong>Nama:</strong> {{ $item->nama ?? '-' }} <br>
            <strong>Man Power ID:</strong> {{ $item->man_power_id ?? '-' }} <br>
            <strong>Station ID:</strong> {{ $item->station_id ?? '-' }} <br>
            <strong>Shift:</strong> {{ $item->shift ?? '-' }} <br>
            <strong>Line Area:</strong> {{ $item->line_area ?? '-' }} <br>
            <strong>Grup:</strong> {{ $item->grup ?? '-' }} <br>
            <strong>Keterangan:</strong> {{ $item->keterangan ?? '-' }} <br>
            <strong>Nama After:</strong> {{ $item->nama_after ?? '-' }} <br>
            <strong>Man Power ID After:</strong> {{ $item->man_power_id_after ?? '-' }} <br>

            <strong>Effective Date:</strong>
            {{ $item->effective_date ? \Carbon\Carbon::parse($item->effective_date)->format('d M Y') : '-' }} <br>

            <strong>End Date:</strong>
            {{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d M Y') : '-' }} <br>

            <strong>Created At:</strong> {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }} <br>

            <strong>Serial Number Start:</strong> {{ $item->serial_number_start ?? '-' }} <br>
            <strong>Serial Number End:</strong> {{ $item->serial_number_end ?? '-' }} <br>

            <strong>Time Start:</strong> {{ $item->time_start ?? '-' }} <br>
            <strong>Time End:</strong> {{ $item->time_end ?? '-' }} <br>

            <strong>Note:</strong> {{ $item->note ?? '-' }} <br>

            <strong>Lampiran:</strong> {{ $item->lampiran ?? '-' }} <br>
            <strong>Lampiran 2:</strong> {{ $item->lampiran_2 ?? '-' }} <br>
            <strong>Lampiran 3:</strong> {{ $item->lampiran_3 ?? '-' }} <br>
        </li>
    @endforeach
</ul>

<p>Mohon segera melakukan approval pada sistem.</p>

<p>Terima kasih.</p>
