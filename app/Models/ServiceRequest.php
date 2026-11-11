<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_id', 'assignee_id', 'title',
        'description', 'category', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'category' => Category::class,
        ];
    }

}
