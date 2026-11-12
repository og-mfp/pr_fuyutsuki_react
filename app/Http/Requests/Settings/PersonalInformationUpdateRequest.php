<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PersonalInformationUpdateRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:2000'],
            'primary_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s().-]*$/'],
            'secondary_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s().-]*$/'],
        ];
    }
}
