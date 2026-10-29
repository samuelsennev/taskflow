<?php

namespace App\Http\Requests;

use App\Services\Tasks\TaskTextParser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class ImportTasksRequest extends FormRequest
{
    public const int MAX_TASKS = 500;

    /**
     * @var list<array{title: string, description: string|null, due_date: Carbon, is_completed: bool, heading: string|null}>|null
     */
    private ?array $parsedTasks = null;

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
            'file' => ['required', 'file', 'max:1024'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Escolha um arquivo para importar.',
            'file.max' => 'O arquivo não pode ter mais de 1 MB.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $parser = app(TaskTextParser::class);
                $contents = (string) $this->file('file')->get();

                if (! $parser->isText($contents)) {
                    $validator->errors()->add('file', 'O arquivo precisa ser de texto.');

                    return;
                }

                $this->parsedTasks = $parser->parse($contents, limit: self::MAX_TASKS + 1);

                if ($this->parsedTasks === []) {
                    $validator->errors()->add('file', 'Nenhuma task encontrada no arquivo.');
                } elseif (count($this->parsedTasks) > self::MAX_TASKS) {
                    $validator->errors()->add('file', 'O arquivo não pode ter mais de '.self::MAX_TASKS.' tasks.');
                }
            },
        ];
    }

    /**
     * The tasks read from the uploaded file.
     *
     * @return list<array{title: string, description: string|null, due_date: Carbon, is_completed: bool, heading: string|null}>
     */
    public function tasks(): array
    {
        return $this->parsedTasks ?? [];
    }
}
