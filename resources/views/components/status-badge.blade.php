@php
    use App\Enums\InvoiceStatus;
    $colorMap = [
        'draft'     => 'bg-gray-100 text-gray-600',
        'sent'      => 'bg-blue-100 text-blue-700',
        'paid'      => 'bg-green-100 text-green-700',
        'overdue'   => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-gray-100 text-gray-400 line-through',
    ];
    $value   = $status instanceof InvoiceStatus ? $status->value : (string) $status;
    $label   = $status instanceof InvoiceStatus ? $status->label() : ucfirst($value);
    $classes = $colorMap[$value] ?? 'bg-gray-100 text-gray-600';
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ $label }}
</span>
