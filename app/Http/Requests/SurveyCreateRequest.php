<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SurveyCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'integration_key' => 'required|string',
            'telegram_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.question' => 'required|string',
            'items.*.answer' => 'required|string',
        ];
    }


    public function messages(): array {
        return [
            'integration_key.required' => 'Integration key is required',
            'integration_key.string' => 'Integration key must be string',
            'telegram_id.required' => 'Telegram id is required',
            'telegram_id.integer' => 'Telegram id must be integer',
            'items.required' => 'Items is required',
            'items.array' => 'Items must be array',
            'items.*.question.required' => 'Question is required',
            'items.*.question.string' => 'Question must be string',
            'items.*.answer.required' => 'Answer is required',
            'items.*.answer.string' => 'Answer must be string',
        ];
    }
}
