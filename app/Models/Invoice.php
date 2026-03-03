<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'invoice_number',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'status'    => InvoiceStatus::class,
        'issued_at' => 'date',
        'due_at'    => 'date',
        'paid_at'   => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    public function getTotalAttribute(): int
    {
        return (int) $this->lineItems->sum(fn (InvoiceLineItem $item) => $item->total);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at->isPast() && $this->status === InvoiceStatus::Sent;
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_at', '<', now())
            ->where('status', InvoiceStatus::Sent->value);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InvoiceStatus::Sent->value,
            InvoiceStatus::Overdue->value,
        ]);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Draft->value);
    }
}
