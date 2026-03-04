<x-app-layout>
    <x-slot name="heading">Work Logs</x-slot>

    @if ($workLogs->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <p class="text-gray-400 text-sm">No work logs recorded yet.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($workLogs as $clientId => $logs)
                @php
                    $client       = $logs->first()->client;
                    $unbilledLogs = $logs->where('status.value', 'unbilled');
                    $billedLogs   = $logs->where('status.value', 'billed');
                    $unbilledTotal = $unbilledLogs->sum(fn ($l) => (float)$l->hours * $l->rate);
                    $unbilledHours = $unbilledLogs->sum('hours');
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    {{-- Client Header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100"
                         style="background-color: #1a1a2e;">
                        <div>
                            <a href="{{ route('clients.show', $client) }}"
                               class="font-semibold text-white hover:text-indigo-300">
                                {{ $client->name }}
                            </a>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $logs->count() }} entries</p>
                        </div>
                        @if ($unbilledLogs->isNotEmpty())
                            <div class="text-right">
                                <p class="text-sm font-bold text-amber-400">@money((int)$unbilledTotal) unbilled</p>
                                <p class="text-xs text-gray-400">{{ number_format($unbilledHours, 1) }}h @ avg rate</p>
                            </div>
                        @endif
                    </div>

                    {{-- Unbilled entries --}}
                    @if ($unbilledLogs->isNotEmpty())
                        <div class="px-5 py-2 bg-amber-50 border-b border-amber-100">
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">
                                Unbilled — {{ number_format($unbilledHours, 1) }}h
                            </p>
                        </div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-amber-50/50">
                                    <th class="px-5 py-2">Description</th>
                                    <th class="px-5 py-2 text-right">Hours</th>
                                    <th class="px-5 py-2 text-right">Rate</th>
                                    <th class="px-5 py-2 text-right">Amount</th>
                                    <th class="px-5 py-2">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-50">
                                @foreach ($unbilledLogs as $log)
                                    <tr class="bg-amber-50/20 hover:bg-amber-50/50 transition-colors">
                                        <td class="px-5 py-3 text-gray-900">{{ $log->description }}</td>
                                        <td class="px-5 py-3 text-right font-medium text-gray-700">{{ $log->hours }}</td>
                                        <td class="px-5 py-3 text-right text-gray-500">@money($log->rate)/hr</td>
                                        <td class="px-5 py-3 text-right font-semibold text-amber-700">
                                            @money((int) round((float)$log->hours * $log->rate))
                                        </td>
                                        <td class="px-5 py-3 text-gray-500">{{ $log->worked_at->format('M j, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-amber-200 bg-amber-50">
                                    <td colspan="3" class="px-5 py-3 text-right text-xs font-bold text-amber-700 uppercase tracking-wider">
                                        Unbilled Total
                                    </td>
                                    <td class="px-5 py-3 text-right font-bold text-amber-700">
                                        @money((int)$unbilledTotal)
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    @endif

                    {{-- Billed entries --}}
                    @if ($billedLogs->isNotEmpty())
                        <div class="px-5 py-2 bg-gray-50 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Billed</p>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($billedLogs as $log)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-5 py-3 text-gray-500 w-full">{{ $log->description }}</td>
                                        <td class="px-5 py-3 text-right text-gray-400 whitespace-nowrap">{{ $log->hours }}h</td>
                                        <td class="px-5 py-3 text-right text-gray-400 whitespace-nowrap">@money($log->rate)/hr</td>
                                        <td class="px-5 py-3 text-right text-gray-500 whitespace-nowrap">
                                            @money((int) round((float)$log->hours * $log->rate))
                                        </td>
                                        <td class="px-5 py-3 text-gray-400 whitespace-nowrap">{{ $log->worked_at->format('M j, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
