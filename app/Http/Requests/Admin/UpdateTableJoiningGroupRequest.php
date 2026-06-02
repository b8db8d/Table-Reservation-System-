<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableJoiningGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'min_guests' => ['required', 'integer', 'min:1', 'max:50'],
            'table_ids' => ['required', 'array', 'min:1'],
            'table_ids.*' => ['integer', 'exists:restaurant_tables,id'],
        ];
    }
}
