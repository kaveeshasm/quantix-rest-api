<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Laravel\Sanctum\Sanctum;

class InvalidTransitionTest extends ApiTestCase
{
    public function test_cannot_submit_an_already_submitted_request(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/requests/{$request->id}/submit")->assertStatus(422);
    }

    public function test_cannot_approve_a_draft(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Draft);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertStatus(422);
    }

    public function test_cannot_assign_a_request_that_is_not_approved(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->ops);

        $this->postJson("/api/requests/{$request->id}/assign", ['assignee_id' => $this->ops->id])
            ->assertStatus(422);
    }

    public function test_cannot_complete_before_starting(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Assigned, [
            'assignee_id' => $this->ops->id,
        ]);
        Sanctum::actingAs($this->ops);

        $this->postJson("/api/requests/{$request->id}/status", ['status' => 'completed'])
            ->assertStatus(422);
    }

    public function test_rejected_request_is_final(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Rejected);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertStatus(422);
    }

    public function test_completed_request_is_final(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Completed);
        Sanctum::actingAs($this->ops);

        $this->postJson("/api/requests/{$request->id}/status", ['status' => 'in_progress'])
            ->assertStatus(422);
    }

    public function test_cannot_edit_a_submitted_request(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->alice);

        $this->putJson("/api/requests/{$request->id}", ['title' => 'Changed'])
            ->assertStatus(422);

        $this->assertNotSame('Changed', ServiceRequest::find($request->id)->title);
    }

    public function test_return_requires_a_comment(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'return'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('comment');
    }

    public function test_assignee_must_be_an_operations_user(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Approved);
        Sanctum::actingAs($this->ops);

        $this->postJson("/api/requests/{$request->id}/assign", ['assignee_id' => $this->manager->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assignee_id');
    }
}
