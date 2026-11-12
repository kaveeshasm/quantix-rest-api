<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\Category;
use App\Enums\RequestStatus;
use App\Models\User;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(Category::cases()),
            'status' => RequestStatus::Draft,
        ];
    }

    public function withStatus(RequestStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Assigned,
            'assignee_id' => User::factory()->operations(),
        ]);
    }
}
