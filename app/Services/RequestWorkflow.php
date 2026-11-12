<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestWorkflow
{
    /**
     * Move a request to a new status, record it in the activity log.
     *
     * @param  array<string, mixed>  $attributes  extra columns to save in the same update (e.g. assignee_id)
     */
    public function transition(
        ServiceRequest $request,
        RequestStatus $to,
        User $actor,
        ?string $comment = null,
        array $attributes = [],
    ): ServiceRequest {
        $from = $request->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidTransitionException::between($from, $to);
        }

        return DB::transaction(function () use ($request, $from, $to, $actor, $comment, $attributes) {
            $request->update(['status' => $to] + $attributes);

            $request->activityLogs()->create([
                'user_id' => $actor->id,
                'action' => $to->value,
                'from_status' => $from,
                'to_status' => $to,
                'comment' => $comment,
            ]);

            return $request;
        });
    }
}