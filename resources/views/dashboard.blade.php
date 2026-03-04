<x-app-layout>
    <x-slot name="heading">Dashboard</x-slot>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Invoiced (YTD)</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">@money($totalInvoiced)</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Collected (YTD)</p>
            <p class="mt-2 text-2xl font-bold text-green-600">@money($totalPaid)</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">@money($outstanding)</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue Invoices</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ $overdueCount }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Needs Attention: Overdue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Needs Attention</h2>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                    {{ $overdueInvoices->count() }} overdue
                </span>
            </div>
            @if ($overdueInvoices->isEmpty())
                <p class="px-5 py-8 text-sm text-gray-400 text-center">No overdue invoices.</p>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach ($overdueInvoices as $invoice)
                        @php $daysOverdue = $invoice->due_at->diffInDays(now()); @endphp
                        <li class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div>
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $invoice->client->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">@money($invoice->total)</p>
                                <p class="text-xs text-red-600 font-medium mt-0.5">{{ $daysOverdue }}d overdue</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Draft Invoices --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Draft Invoices</h2>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                    {{ $draftInvoices->count() }} unsent
                </span>
            </div>
            @if ($draftInvoices->isEmpty())
                <p class="px-5 py-8 text-sm text-gray-400 text-center">No draft invoices.</p>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach ($draftInvoices as $invoice)
                        <li class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div>
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $invoice->client->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">@money($invoice->total)</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $invoice->issued_at->format('M j, Y') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Recently Paid --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 lg:col-span-2">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Recently Paid</h2>
            </div>
            @if ($recentPaid->isEmpty())
                <p class="px-5 py-8 text-sm text-gray-400 text-center">No paid invoices yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="px-5 py-3 bg-gray-50">Invoice</th>
                                <th class="px-5 py-3 bg-gray-50">Client</th>
                                <th class="px-5 py-3 bg-gray-50 text-right">Amount</th>
                                <th class="px-5 py-3 bg-gray-50">Paid On</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($recentPaid as $invoice)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('invoices.show', $invoice) }}"
                                           class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ $invoice->client->name }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-gray-900">@money($invoice->total)</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $invoice->paid_at?->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
