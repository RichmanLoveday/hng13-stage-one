<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStringAnalyzer extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value' => 'required|string|unique:string_analyzers,input_string',
        ];
    }

    public function messages()
    {
        return [
            'value.required' => "Invalid request body or missing  'value' field.",
            'value.string' => "The 'value' field must be a string.",
            'value.unique' => "String already exists in system."
        ];
    }

    /**
     * Customize validation failure response and status codes for specific errors.
     */
    protected function failedValidation(Validator $validator): void
    {
        //? get validation errors
        $errors = $validator->errors()->toArray();
        $status = 422; //? default for validation errors

        //? check specific failed rules to set appropriate status codes
        $failedRules = $validator->failed();

        if (isset($failedRules['value']['Required'])) $status = 400;   //? check if vaule is missing
        if (isset($failedRules['value']['String'])) $status = 422;   //? check if value is not a string
        if (isset($failedRules['value']['Unique'])) $status = 409;   //? check if string already exists

        //? throw json response with errors ana status code
        throw new HttpResponseException(response()->json([
            //'message' => 'The given data was invalid.',
            'message' => $errors['value'][0] ?? 'Validation failed',
            'code' => $status,
        ], $status));
    }
}
