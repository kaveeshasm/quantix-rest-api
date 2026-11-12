<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Models\Comment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    private User $manager;

    private User $ops;

    public function run(): void
    {
        $alice = User::where('email', 'alice@example.com')->firstOrFail();
        $bob = User::where('email', 'bob@example.com')->firstOrFail();
        $this->manager = User::where('email', 'manager@example.com')->firstOrFail();
        $this->ops = User::where('email', 'ops@example.com')->firstOrFail();

        $S = RequestStatus::class;

        // 1-2 draft
        $this->seed($alice, 'Replacement keyboard', []);
        $this->seed($bob, 'Request for second monitor', []);

        // 3-4 submitted (waiting for the manager)
        $this->seed($alice, 'VPN access for contractor', [$S::Submitted]);
        $this->seed($bob, 'Repair broken office chair', [$S::Submitted]);

        // 5 returned
        $returned = $this->seed($bob, 'Install design software', [$S::Submitted, $S::Returned]);
        Comment::create([
            'service_request_id' => $returned->id,
            'user_id' => $this->manager->id,
            'body' => 'Please include the license cost and a business reason.',
        ]);

        // 6 approved (waiting for Operations)
        $this->seed($alice, 'New laptop for onboarding', [$S::Submitted, $S::Approved]);

        // 7 rejected (final)
        $this->seed($bob, 'Standing desk upgrade', [$S::Submitted, $S::Rejected]);

        // 8 assigned
        $this->seed($alice, 'Meeting room projector fix', [$S::Submitted, $S::Approved, $S::Assigned]);

        // 9 in progress
        $this->seed($bob, 'Database access request', [$S::Submitted, $S::Approved, $S::Assigned, $S::InProgress]);

        // 10 completed (final)
        $done = $this->seed($alice, 'Email account setup', [
            $S::Submitted, $S::Approved, $S::Assigned, $S::InProgress, $S::Completed,
        ]);
        Comment::create([
            'service_request_id' => $done->id,
            'user_id' => $this->ops->id,
            'body' => 'Account created and tested.',
        ]);
    }

    /**
     * Create a request and walk it through the given statuses,
     * writing an activity log row for each step.
     *
     * @param  list<RequestStatus>  $path
     */
    private function seed(User $requester, string $title, array $path): ServiceRequest
    {
        $request = ServiceRequest::factory()->create([
            'requester_id' => $requester->id,
            'title' => $title,
            'status' => RequestStatus::Draft,
        ]);

        $this->log($request, $requester, 'created', null, RequestStatus::Draft);

        $from = RequestStatus::Draft;

        foreach ($path as $to) {
            $actor = match ($to) {
                RequestStatus::Submitted => $requester,
                RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Returned => $this->manager,
                default => $this->ops,
            };

            $comment = match ($to) {
                RequestStatus::Returned => 'Please add more detail.',
                RequestStatus::Rejected => 'Not approved this quarter.',
                default => null,
            };

            $request->status = $to;

            if ($to === RequestStatus::Assigned) {
                $request->assignee_id = $this->ops->id;
            }

            $request->save();

            $this->log($request, $actor, $to->value, $from, $to, $comment);

            $from = $to;
        }

        return $request;
    }

    private function log(
        ServiceRequest $request,
        User $actor,
        string $action,
        ?RequestStatus $from,
        RequestStatus $to,
        ?string $comment = null,
    ): void {
        $request->activityLogs()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
        ]);
    }
}
