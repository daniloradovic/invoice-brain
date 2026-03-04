<x-app-layout>
    <x-slot name="heading">Invoices</x-slot>

    {{-- Filter Tabs --}}
    <div class="flex gap-1 mb-5 bg-gray-100 p-1 rounded-lg w-fit">
        @php
            $filters = [
                ''          => 'All',
                'draft'     => 'Draft',
                'sent'      => 'Outstanding',
                'overdue'   => 'Overdue',
                'paid'      => 'Paid',
            ];
        @endphp
        @foreach ($filters as $value => $label)
            <a href="{{ route('invoices.index', $value ? ['status' => $value] : []) }}"
               class="px-4 py-2 text-sm font-medium rounded-md transition-all
                      {{ $status === $value || ($status === null && $value === '')
                          ? 'bg-white shadow-sm text-gray-900'
                          : 'text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <p class="text-sm text-gray-500">{{ $invoices->count() }} invoice{{ $invoices->count() === 1 ? '' : 's' }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                        <th class="px-5 py-3">Invoice #</th>
                        <th class="px-5 py-3">Client</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Issued</th>
                        <th class="px-5 py-3">Due</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-gray-700">
                                <a href="{{ route('clients.show', $invoice->client) }}" class="hover:text-indigo-600">
                                    {{ $invoice->client->name }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">@money($invoice->total)</td>
                            <td class="px-5 py-3">
                                @include('partials.status-badge', ['status' => $invoice->status])
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $invoice->issued_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-gray-500">
                                @if ($invoice->is_overdue)
                                    <span class="text-red-600 font-medium">{{ $invoice->due_at->format('M j, Y') }}</span>
                                @else
                                    {{ $invoice->due_at->format('M j, Y') }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
