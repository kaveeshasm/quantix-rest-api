<?php

namespace App\Http\Requests;

use App\Enums\Category;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ServiceRequest::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(Category::class)],
            'submit' => ['sometimes', 'boolean'],
        ];
    }
}
