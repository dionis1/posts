<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateRole', $this->route('user'));
    }

    public function rules(): array
    {
        return ['role' => ['required', Rule::enum(Role::class)]];
    }
}
