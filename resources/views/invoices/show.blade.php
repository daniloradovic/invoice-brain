<x-app-layout>
    <x-slot name="heading">{{ $invoice->invoice_number }}</x-slot>

    <div class="max-w-4xl">

        {{-- Invoice Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-xl font-bold text-gray-900">{{ $invoice->invoice_number }}</h2>
                        @include('partials.status-badge', ['status' => $invoice->status])
                    </div>
                    <a href="{{ route('clients.show', $invoice->client) }}"
                       class="text-gray-600 hover:text-indigo-600 font-medium">
                        {{ $invoice->client->name }}
                    </a>
                    <p class="text-sm text-gray-400 mt-0.5">{{ $invoice->client->email }}</p>
                </div>
                <div class="text-right space-y-1">
                    <div class="flex gap-8 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Issued</p>
                            <p class="font-medium text-gray-900">{{ $invoice->issued_at->format('M j, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Due</p>
                            <p class="font-medium {{ $invoice->is_overdue ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $invoice->due_at->format('M j, Y') }}
                            </p>
                        </div>
                        @if ($invoice->paid_at)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Paid</p>
                                <p class="font-medium text-green-600">{{ $invoice->paid_at->format('M j, Y') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Line Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                            <th class="px-5 py-3">Description</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Unit Price</th>
                            <th class="px-5 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($invoice->lineItems as $item)
                            <tr>
                                <td class="px-5 py-3 text-gray-800">{{ $item->description }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ $item->quantity }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">@money($item->unit_price)</td>
                                <td class="px-5 py-3 text-right font-medium text-gray-900">@money($item->total)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-400">No line items.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($invoice->lineItems->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="3" class="px-5 py-4 text-right font-bold text-gray-900">Total</td>
                                <td class="px-5 py-4 text-right font-bold text-xl text-gray-900">@money($invoice->total)</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Notes --}}
        @if ($invoice->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Notes</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line leading-relaxed">{{ $invoice->notes }}</p>
            </div>
        @endif

    </div>
</x-app-layout>
