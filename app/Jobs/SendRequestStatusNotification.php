<?php

namespace App\Jobs;

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\RequestStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendRequestStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ServiceRequest $serviceRequest,
        public RequestStatus $to,
        public int $actorId,
        public ?string $comment = null,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->serviceRequest->loadMissing('requester.manager', 'assignee');

        $actor = User::find($this->actorId);

        $recipients = $this->recipients()
            ->filter()
            ->reject(fn (User $user) => $user->id === $this->actorId)
            ->unique('id');

        Notification::send($recipients, new RequestStatusChanged(
            $this->serviceRequest,
            $this->to,
            $actor?->name ?? 'Someone',
            $this->comment,
        ));
    }

    /** @return Collection<int, User|null> */
    private function recipients(): Collection
    {
        $requester = $this->serviceRequest->requester;

        return match ($this->to) {
            RequestStatus::Submitted => collect([$requester->manager]),
            RequestStatus::Approved => User::where('role', Role::Operations->value)->get()->push($requester),
            RequestStatus::Assigned => collect([$this->serviceRequest->assignee, $requester]),
            RequestStatus::Rejected,
            RequestStatus::Returned,
            RequestStatus::InProgress,
            RequestStatus::Completed => collect([$requester]),
            RequestStatus::Draft => collect(),
        };
    }
}
