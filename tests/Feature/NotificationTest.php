<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Jobs\SendRequestStatusNotification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

class NotificationTest extends ApiTestCase
{
    public function test_job_is_dispatched_when_status_changes(): void
    {
        Queue::fake();

        $request = $this->createRequest($this->alice, RequestStatus::Submitted);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertOk();

        Queue::assertPushed(
            SendRequestStatusNotification::class,
            fn ($job) => $job->serviceRequest->id === $request->id
                && $job->to === RequestStatus::Approved
        );
    }

    public function test_no_job_is_dispatched_when_the_transition_is_invalid(): void
    {
        Queue::fake();

        $request = $this->createRequest($this->alice, RequestStatus::Draft);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/requests/{$request->id}/decision", ['decision' => 'approve'])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_manager_receives_a_database_notification_on_submit(): void
    {
        // QUEUE_CONNECTION=sync in phpunit.xml runs the job immediately
        $request = $this->createRequest($this->alice);
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/requests/{$request->id}/submit")->assertOk();

        $this->assertCount(1, $this->manager->notifications);
        $this->assertSame('submitted', $this->manager->notifications->first()->data['status']);
        $this->assertCount(0, $this->alice->notifications); // actor is not notified
    }
}