<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AuthorizationTest extends ApiTestCase
{
    public function test_operations_cannot_decide(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->ops);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertForbidden();
    }

    public function test_employee_cannot_decide(): void
    {
        $request = $this->createRequest($this->bob, RequestStatus::Submitted);
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertForbidden();
    }

    public function test_manager_cannot_decide_on_another_managers_report(): void
    {
        $otherManager = User::factory()->manager()->create();
        $stranger = User::factory()->reportingTo($otherManager)->create();
        $request = $this->createRequest($stranger, RequestStatus::Submitted);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertForbidden();
    }

    public function test_employee_cannot_assign(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Approved);
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/requests/{$request->id}/assign", ['assignee_id' => $this->ops->id])
            ->assertForbidden();
    }

    public function test_manager_cannot_change_work_status(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Assigned);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/status", ['status' => 'in_progress'])
            ->assertForbidden();
    }

    public function test_employee_cannot_edit_someone_elses_request(): void
    {
        $request = $this->createRequest($this->alice);
        Sanctum::actingAs($this->bob);

        $this->putJson("/api/requests/{$request->id}", ['title' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_employee_cannot_submit_someone_elses_request(): void
    {
        $request = $this->createRequest($this->alice);
        Sanctum::actingAs($this->bob);

        $this->postJson("/api/requests/{$request->id}/submit")->assertForbidden();
    }

    public function test_employee_cannot_view_someone_elses_request(): void
    {
        $request = $this->createRequest($this->alice);
        Sanctum::actingAs($this->bob);

        $this->getJson("/api/requests/{$request->id}")->assertForbidden();
    }

    public function test_participant_can_comment(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/comments", ['body' => 'Looks fine'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Looks fine');
    }

    public function test_non_participant_cannot_comment(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->bob);

        $this->postJson("/api/requests/{$request->id}/comments", ['body' => 'Hello'])
            ->assertForbidden();
    }
}