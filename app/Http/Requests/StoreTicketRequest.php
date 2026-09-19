<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketTarget;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:120'],
            'whatsapp_number' => ['required', 'string', 'regex:/^[0-9+() -]{8,25}$/'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['required', new Enum(TicketPriority::class)],
            'target' => ['required', new Enum(TicketTarget::class)],
        ];
    }
}
