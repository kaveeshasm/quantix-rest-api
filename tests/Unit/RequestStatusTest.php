<?php

namespace Tests\Unit;

use App\Enums\RequestStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RequestStatusTest extends TestCase
{
    public static function validTransitions(): array
    {
        return [
            'draft to submitted' => [RequestStatus::Draft, RequestStatus::Submitted],
            'returned to submitted' => [RequestStatus::Returned, RequestStatus::Submitted],
            'submitted to approved' => [RequestStatus::Submitted, RequestStatus::Approved],
            'submitted to rejected' => [RequestStatus::Submitted, RequestStatus::Rejected],
            'submitted to returned' => [RequestStatus::Submitted, RequestStatus::Returned],
            'approved to assigned' => [RequestStatus::Approved, RequestStatus::Assigned],
            'assigned to in progress' => [RequestStatus::Assigned, RequestStatus::InProgress],
            'in progress to completed' => [RequestStatus::InProgress, RequestStatus::Completed],
        ];
    }

    public static function invalidTransitions(): array
    {
        return [
            'draft to approved' => [RequestStatus::Draft, RequestStatus::Approved],
            'submitted to completed' => [RequestStatus::Submitted, RequestStatus::Completed],
            'approved to in progress' => [RequestStatus::Approved, RequestStatus::InProgress],
            'assigned to completed' => [RequestStatus::Assigned, RequestStatus::Completed],
            'rejected to submitted' => [RequestStatus::Rejected, RequestStatus::Submitted],
            'completed to draft' => [RequestStatus::Completed, RequestStatus::Draft],
        ];
    }

    #[DataProvider('validTransitions')]
    public function test_allows_valid_transitions(RequestStatus $from, RequestStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    #[DataProvider('invalidTransitions')]
    public function test_blocks_invalid_transitions(RequestStatus $from, RequestStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public function test_rejected_and_completed_are_final(): void
    {
        foreach ([RequestStatus::Rejected, RequestStatus::Completed] as $status) {
            $this->assertTrue($status->isFinal());
            $this->assertSame([], $status->allowedTransitions());
        }
    }

    public function test_editing_allowed_only_in_draft_or_returned(): void
    {
        $this->assertTrue(RequestStatus::Draft->isEditable());
        $this->assertTrue(RequestStatus::Returned->isEditable());
        $this->assertFalse(RequestStatus::Submitted->isEditable());
    }
}