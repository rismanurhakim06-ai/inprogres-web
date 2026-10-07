<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateOwnTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && $ticket->user_id === $this->user()?->id;
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
            'remove_attachments' => ['nullable', 'array', 'max:5'],
            'remove_attachments.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('ticket_attachments', 'id')
                    ->where('ticket_id', $this->route('ticket')?->id),
            ],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ticket = $this->route('ticket');

                if (! $ticket instanceof Ticket || $validator->errors()->has('remove_attachments')) {
                    return;
                }

                $removalIds = collect($this->input('remove_attachments', []))
                    ->filter(fn (mixed $id): bool => is_numeric($id))
                    ->map(fn (mixed $id): int => (int) $id);
                $attachmentCountAfterRemoval = $ticket->attachments()
                    ->whereNotIn('id', $removalIds)
                    ->count();
                $newAttachmentCount = count($this->file('attachments', []));

                if ($attachmentCountAfterRemoval + $newAttachmentCount > 5) {
                    $validator->errors()->add('attachments', 'Jumlah lampiran setelah perubahan maksimal 5 file.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => 'Maksimal 5 file dapat diunggah sekaligus.',
            'attachments.*.mimes' => 'Lampiran harus berupa gambar, PDF, Word, atau Excel yang valid.',
            'attachments.*.extensions' => 'Format lampiran yang didukung: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, dan XLSX.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
            'remove_attachments.*.exists' => 'Lampiran yang dipilih tidak ditemukan pada tiket ini.',
        ];
    }
}
