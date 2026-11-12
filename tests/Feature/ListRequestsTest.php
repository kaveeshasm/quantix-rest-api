<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Comment;
use App\Models\ServiceRequest;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ListRequestsTest extends ApiTestCase
{
    public function test_employee_sees_only_their_own_requests(): void
    {
        $this->createRequest($this->alice);
        $this->createRequest($this->alice);
        $this->createRequest($this->bob);

        Sanctum::actingAs($this->alice);

        $this->getJson('/api/requests')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_manager_sees_reports_requests_but_not_drafts_or_other_teams(): void
    {
        $this->createRequest($this->alice, RequestStatus::Draft);
        $this->createRequest($this->alice, RequestStatus::Submitted);
        $this->createRequest($this->bob, RequestStatus::Approved);

        $otherManager = User::factory()->manager()->create();
        $stranger = User::factory()->reportingTo($otherManager)->create();
        $this->createRequest($stranger, RequestStatus::Submitted);

        Sanctum::actingAs($this->manager);

        $this->getJson('/api/requests')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_operations_sees_only_approved_and_later(): void
    {
        $this->createRequest($this->alice, RequestStatus::Draft);
        $this->createRequest($this->alice, RequestStatus::Submitted);
        $this->createRequest($this->alice, RequestStatus::Approved);
        $this->createRequest($this->bob, RequestStatus::Completed);

        Sanctum::actingAs($this->ops);

        $this->getJson('/api/requests')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_status_filter_and_search(): void
    {
        $this->createRequest($this->alice, RequestStatus::Draft, ['title' => 'Printer toner']);
        $this->createRequest($this->alice, RequestStatus::Submitted, ['title' => 'New laptop']);

        Sanctum::actingAs($this->alice);

        $this->getJson('/api/requests?status=submitted')->assertJsonCount(1, 'data');
        $this->getJson('/api/requests?search=toner')->assertJsonCount(1, 'data');
    }

    public function test_invalid_sort_column_is_rejected(): void
    {
        Sanctum::actingAs($this->alice);

        $this->getJson('/api/requests?sort=password')->assertStatus(422);
    }

    public function test_list_is_paginated_with_metadata(): void
    {
        ServiceRequest::factory()->count(20)->create(['requester_id' => $this->alice->id]);
        Sanctum::actingAs($this->alice);

        $this->getJson('/api/requests?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
    }

    public function test_show_includes_comments_and_activity(): void
    {
        $request = $this->createRequest($this->alice);
        Comment::factory()->create(['service_request_id' => $request->id, 'user_id' => $this->alice->id]);
        $request->activityLogs()->create([
            'user_id' => $this->alice->id,
            'action' => 'created',
            'to_status' => RequestStatus::Draft,
        ]);

        Sanctum::actingAs($this->alice);

        $this->getJson("/api/requests/{$request->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonCount(1, 'data.activity');
    }

    public function test_missing_request_returns_404(): void
    {
        Sanctum::actingAs($this->alice);

        $this->getJson('/api/requests/999')->assertNotFound();
    }
}
