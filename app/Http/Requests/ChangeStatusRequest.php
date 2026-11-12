<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('serviceRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                RequestStatus::InProgress->value,
                RequestStatus::Completed->value,
            ])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
