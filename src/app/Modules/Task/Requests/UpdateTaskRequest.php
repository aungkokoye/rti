<?php
declare(strict_types=1);

namespace App\Modules\Task\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
        $rules = [
            'assigned_to'       => 'nullable|exists:users,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'status'            => 'required|string|in:' . implode(',', \App\Modules\Task\Models\Task::$status),
            'priority'          => 'required|string|in:' . implode(',', \App\Modules\Task\Models\Task::$priority),
            'metadata'          => 'nullable|array',
                'metadata.location' => ['required', 'string'],
                'metadata.link'     => ['required', 'url'],
                'metadata.uuid'     => ['required', 'uuid'],
            'tags'              => ['nullable', 'array'],
                'tags.*'            => ['exists:tags,id'],
        ];

        // If status is completed, allow any date. Otherwise, require future date.
        if ($this->input('status') === 'completed') {
            $rules['due_date'] = 'nullable|date';
        } else {
            $rules['due_date'] = 'nullable|date|after:today';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'The selected user does not exist.',
            'status.in'          => 'The status should be:' . implode(', ', \App\Modules\Task\Models\Task::$status) . '.',
            'priority.in'        => 'The priority should be:' . implode(', ', \App\Modules\Task\Models\Task::$priority) . '.',
            'tags.*.exists'      => 'One or more selected tags do not exist.'
        ];
    }
}
