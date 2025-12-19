<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaidLeaveUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_year_taken'     => 'required|integer|min:0',
            'last_year_taken'        => 'required|integer|min:0',
            'two_years_ago_taken'    => 'required|integer|min:0',
            'two_years_ago_granted'  => 'required|integer|min:0',
            'two_years_ago_carried'  => 'required|integer|min:0',
        ];
    }
}
