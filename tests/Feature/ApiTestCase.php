<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $alice;

    protected User $bob;

    protected User $ops;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->manager()->create();
        $this->alice = User::factory()->reportingTo($this->manager)->create();
        $this->bob = User::factory()->reportingTo($this->manager)->create();
        $this->ops = User::factory()->operations()->create();
    }

    protected function createRequest(
        User $requester,
        RequestStatus $status = RequestStatus::Draft,
        array $attributes = [],
    ): ServiceRequest {
        return ServiceRequest::factory()->create([
            'requester_id' => $requester->id,
            'status' => $status,
        ] + $attributes);
    }
}
