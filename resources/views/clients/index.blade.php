<x-app-layout>
    <x-slot name="heading">Clients</x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $clients->count() }} clients total</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Default Rate</th>
                        <th class="px-5 py-3 text-center">Open Invoices</th>
                        <th class="px-5 py-3 text-right">Unbilled Hours</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($clients as $client)
                        @php
                            $openCount = $client->invoices
                                ->whereIn('status.value', ['sent', 'overdue'])
                                ->count();
                            $unbilledHours = $client->workLogs
                                ->where('status.value', 'unbilled')
                                ->sum('hours');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4">
                                <a href="{{ route('clients.show', $client) }}"
                                   class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $client->name }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $client->email }}</td>
                            <td class="px-5 py-4 text-gray-700">
                                @if ($client->default_rate)
                                    @money($client->default_rate)/hr
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if ($openCount > 0)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                        {{ $openCount }}
                                    </span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if ($unbilledHours > 0)
                                    <span class="font-medium text-amber-600">{{ number_format($unbilledHours, 1) }}h</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('clients.show', $client) }}"
                                   class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-400">No clients yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
