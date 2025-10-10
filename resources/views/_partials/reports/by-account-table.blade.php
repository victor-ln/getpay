<div class="table-responsive text-nowrap">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>User Name</th>
                <th>Document</th>
                <th class="text-end">Volume IN</th>
                <th class="text-center"># IN</th>
                <th class="text-end">Volume OUT</th>
                <th class="text-center"># OUT</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $row)
            <tr>
                <td><strong>{{ $row->name }}</strong></td>
                <td>{{ $row->document }}</td>
                <td class="text-end">R$ {{ number_format($row->volume_in, 2, ',', '.') }}</td>
                <td class="text-center">{{ $row->count_in }}</td>
                <td class="text-end">R$ {{ number_format($row->volume_out, 2, ',', '.') }}</td>
                <td class="text-center">{{ $row->count_out }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center p-4">No user data found for this account in the selected period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $data->links() }}</div>