<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
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
        return $this->user()?->role === 'user';
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
            'target' => ['required', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
                'extensions:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => 'Lampiran maksimal 5 file.',
            'attachments.*.mimes' => 'Lampiran harus berupa gambar, PDF, Word, atau Excel yang valid.',
            'attachments.*.extensions' => 'Format lampiran yang didukung: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, dan XLSX.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
        ];
    }
}
