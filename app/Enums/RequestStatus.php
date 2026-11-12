<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Completed]);
    }

    /**
     * The statuses this status is allowed to move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft, self::Returned => [self::Submitted],
            self::Submitted => [self::Approved, self::Rejected, self::Returned],
            self::Approved => [self::Assigned],
            self::Assigned => [self::InProgress],
            self::InProgress => [self::Completed],
            self::Rejected, self::Completed => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    /** @return list<self> */
    public static function visibleToOperations(): array
    {
        return [self::Approved, self::Assigned, self::InProgress, self::Completed];
    }

    public function isVisibleToOperations(): bool
    {
        return in_array($this, self::visibleToOperations(), true);
    }
}
