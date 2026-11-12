<?php

use App\Enums\RequestStatus;

it('allows the valid transitions', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    [RequestStatus::Draft, RequestStatus::Submitted],
    [RequestStatus::Returned, RequestStatus::Submitted],
    [RequestStatus::Submitted, RequestStatus::Approved],
    [RequestStatus::Submitted, RequestStatus::Rejected],
    [RequestStatus::Submitted, RequestStatus::Returned],
    [RequestStatus::Approved, RequestStatus::Assigned],
    [RequestStatus::Assigned, RequestStatus::InProgress],
    [RequestStatus::InProgress, RequestStatus::Completed],
]);

it('blocks invalid transitions', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    [RequestStatus::Draft, RequestStatus::Approved],
    [RequestStatus::Submitted, RequestStatus::Completed],
    [RequestStatus::Approved, RequestStatus::InProgress],
    [RequestStatus::Assigned, RequestStatus::Completed],
]);

it('treats rejected and completed as final', function (RequestStatus $status) {
    expect($status->isFinal())->toBeTrue()
        ->and($status->allowedTransitions())->toBeEmpty();
})->with([RequestStatus::Rejected, RequestStatus::Completed]);

it('allows editing only in draft or returned', function () {
    expect(RequestStatus::Draft->isEditable())->toBeTrue()
        ->and(RequestStatus::Returned->isEditable())->toBeTrue()
        ->and(RequestStatus::Submitted->isEditable())->toBeFalse();
});