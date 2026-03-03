<?php

namespace App\Models;

use App\Enums\WorkLogStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'invoice_id',
        'description',
        'hours',
        'rate',
        'worked_at',
        'status',
    ];

    protected $casts = [
        'status'    => WorkLogStatus::class,
        'worked_at' => 'date',
        'hours'     => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->where('status', WorkLogStatus::Unbilled->value);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }
}
