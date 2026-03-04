<x-app-layout>
    <x-slot name="heading">{{ $client->name }}</x-slot>

    {{-- Client Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-wrap items-start gap-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold text-gray-900">{{ $client->name }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $client->email }}</p>
                @if ($client->address)
                    <p class="text-sm text-gray-500 mt-1 whitespace-pre-line">{{ $client->address }}</p>
                @endif
            </div>
            <div class="flex gap-6 text-center">
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $client->invoices->count() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Invoices</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">
                        {{ number_format($client->workLogs->where('status.value', 'unbilled')->sum('hours'), 1) }}h
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">Unbilled</p>
                </div>
                @if ($client->default_rate)
                    <div>
                        <p class="text-2xl font-bold text-gray-900">@money($client->default_rate)</p>
                        <p class="text-xs text-gray-500 mt-0.5">Default Rate/hr</p>
                    </div>
                @endif
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $client->payment_terms }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Net Days</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'invoices' }">
        <div class="flex gap-1 mb-4 bg-gray-100 p-1 rounded-lg w-fit">
            <button @click="tab = 'invoices'"
                    :class="tab === 'invoices' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                Invoices
            </button>
            <button @click="tab = 'worklogs'"
                    :class="tab === 'worklogs' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                Work Logs
            </button>
            <button @click="tab = 'notes'"
                    :class="tab === 'notes' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                Notes
            </button>
        </div>

        {{-- Invoices Tab --}}
        <div x-show="tab === 'invoices'" x-cloak>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @if ($client->invoices->isEmpty())
                    <p class="px-5 py-10 text-center text-gray-400 text-sm">No invoices for this client.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                                    <th class="px-5 py-3">Invoice #</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                    <th class="px-5 py-3">Issued</th>
                                    <th class="px-5 py-3">Due</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($client->invoices->sortByDesc('issued_at') as $invoice)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-5 py-3 font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                                        <td class="px-5 py-3">
                                            @include('partials.status-badge', ['status' => $invoice->status])
                                        </td>
                                        <td class="px-5 py-3 text-right font-semibold">@money($invoice->total)</td>
                                        <td class="px-5 py-3 text-gray-500">{{ $invoice->issued_at->format('M j, Y') }}</td>
                                        <td class="px-5 py-3 text-gray-500">{{ $invoice->due_at->format('M j, Y') }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('invoices.show', $invoice) }}"
                                               class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View →</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Work Logs Tab --}}
        <div x-show="tab === 'worklogs'" x-cloak>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @php
                    $unbilledLogs = $client->workLogs->where('status.value', 'unbilled');
                    $billedLogs   = $client->workLogs->where('status.value', 'billed');
                @endphp
                @if ($client->workLogs->isEmpty())
                    <p class="px-5 py-10 text-center text-gray-400 text-sm">No work logs for this client.</p>
                @else
                    @if ($unbilledLogs->isNotEmpty())
                        <div class="px-5 py-3 bg-amber-50 border-b border-amber-100">
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">
                                Unbilled — {{ number_format($unbilledLogs->sum('hours'), 1) }}h total
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                                        <th class="px-5 py-3">Description</th>
                                        <th class="px-5 py-3 text-right">Hours</th>
                                        <th class="px-5 py-3 text-right">Rate</th>
                                        <th class="px-5 py-3 text-right">Amount</th>
                                        <th class="px-5 py-3">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach ($unbilledLogs->sortByDesc('worked_at') as $log)
                                        <tr class="bg-amber-50/30">
                                            <td class="px-5 py-3 text-gray-900">{{ $log->description }}</td>
                                            <td class="px-5 py-3 text-right text-gray-700">{{ $log->hours }}</td>
                                            <td class="px-5 py-3 text-right text-gray-500">@money($log->rate)/hr</td>
                                            <td class="px-5 py-3 text-right font-semibold text-amber-700">
                                                @money((int) round($log->hours * $log->rate))
                                            </td>
                                            <td class="px-5 py-3 text-gray-500">{{ $log->worked_at->format('M j, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($billedLogs->isNotEmpty())
                        <div class="px-5 py-3 bg-gray-50 border-y border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Billed</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-50">
                                    @foreach ($billedLogs->sortByDesc('worked_at') as $log)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-5 py-3 text-gray-500">{{ $log->description }}</td>
                                            <td class="px-5 py-3 text-right text-gray-400">{{ $log->hours }}</td>
                                            <td class="px-5 py-3 text-right text-gray-400">@money($log->rate)/hr</td>
                                            <td class="px-5 py-3 text-right text-gray-500">
                                                @money((int) round($log->hours * $log->rate))
                                            </td>
                                            <td class="px-5 py-3 text-gray-400">{{ $log->worked_at->format('M j, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Notes Tab --}}
        <div x-show="tab === 'notes'" x-cloak>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                @if ($client->notes)
                    <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $client->notes }}</p>
                @else
                    <p class="text-sm text-gray-400 text-center py-6">No notes for this client.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
