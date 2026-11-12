<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\RequsetStatus;
use App\Models\ServiceRequest;
use App\Models\User;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'user_id' => User::factory(),
            'action' => 'created',
            'from_status' => null,
            'to_status' => RequestStatus::Draft,
            'comment' => null,
        ];
    }
}
