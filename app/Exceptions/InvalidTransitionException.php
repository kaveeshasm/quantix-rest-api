<?php

namespace App\Exceptions;

use App\Enums\RequestStatus;
use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTransitionException extends Exception
{
    public static function between(RequestStatus $from, RequestStatus $to): self
    {
        return new self(
            "A request cannot move from '{$from->value}' to '{$to->value}'."
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
