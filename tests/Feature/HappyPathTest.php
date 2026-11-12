<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use Laravel\Sanctum\Sanctum;

class HappyPathTest extends ApiTestCase
{
    public function test_request_goes_from_creation_to_completion(): void
    {
        Sanctum::actingAs($this->alice);
        $id = $this->postJson('/api/requests', [
            'title' => 'Laptop',
            'description' => 'Need one',
            'category' => 'equipment',
        ])->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.id');

        $this->postJson("/api/requests/{$id}/submit")
            ->assertOk()->assertJsonPath('data.status', 'submitted');

        Sanctum::actingAs($this->manager);
        $this->postJson("/api/requests/{$id}/decision", ['decision' => 'approve'])
            ->assertOk()->assertJsonPath('data.status', 'approved');

        Sanctum::actingAs($this->ops);
        $this->postJson("/api/requests/{$id}/assign", ['assignee_id' => $this->ops->id])
            ->assertOk()->assertJsonPath('data.status', 'assigned');
        $this->postJson("/api/requests/{$id}/status", ['status' => 'in_progress'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->postJson("/api/requests/{$id}/status", ['status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed');

        $request = ServiceRequest::findOrFail($id);
        $this->assertSame(RequestStatus::Completed, $request->status);
        $this->assertSame($this->ops->id, $request->assignee_id);
        // created, submitted, approved, assigned, in_progress, completed
        $this->assertSame(6, ActivityLog::where('service_request_id', $id)->count());
    }

    public function test_returned_request_can_be_edited_and_resubmitted(): void
    {
        $request = $this->createRequest($this->alice, RequestStatus::Submitted);

        Sanctum::actingAs($this->manager);
        $this->postJson("/api/requests/{$request->id}/decision", [
            'decision' => 'return',
            'comment' => 'Add more detail',
        ])->assertOk()->assertJsonPath('data.status', 'returned');

        Sanctum::actingAs($this->alice);
        $this->putJson("/api/requests/{$request->id}", ['title' => 'Better title'])
            ->assertOk()->assertJsonPath('data.title', 'Better title');
        $this->postJson("/api/requests/{$request->id}/submit")
            ->assertOk()->assertJsonPath('data.status', 'submitted');
    }
}
