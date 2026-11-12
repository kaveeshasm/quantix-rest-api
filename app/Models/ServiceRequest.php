<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\RequestStatus;
use App\Enums\Role;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'assignee_id',
        'title',
        'description',
        'category',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'category' => Category::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            Role::Employee => $query->where('requester_id', $user->id),
            Role::Manager => $query
                ->whereIn('requester_id', $user->directReports()->select('id'))
                ->where('status', '!=', RequestStatus::Draft->value),
            Role::Operations => $query->whereIn(
                'status',
                array_map(fn ($s) => $s->value, RequestStatus::visibleToOperations())
            ),
        };
    }
}
