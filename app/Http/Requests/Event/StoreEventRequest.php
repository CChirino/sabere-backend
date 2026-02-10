<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'director']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:academic,sports,cultural,administrative'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'visibility' => ['required', 'in:all,teachers,students,staff'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'send_notification' => ['boolean'],
            'status' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede exceder 255 caracteres.',
            'type.required' => 'El tipo de evento es obligatorio.',
            'type.in' => 'El tipo de evento no es válido.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio no es válida.',
            'end_date.date' => 'La fecha de fin no es válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'visibility.required' => 'La visibilidad es obligatoria.',
            'visibility.in' => 'La visibilidad seleccionada no es válida.',
            'location.max' => 'La ubicación no puede exceder 255 caracteres.',
            'color.max' => 'El color debe tener máximo 7 caracteres.',
            'academic_period_id.exists' => 'El período académico seleccionado no existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir strings vacíos a null para campos nullable
        $this->merge(array_map(
            fn ($value) => $value === '' ? null : $value,
            $this->only([
                'description', 'end_date', 'location', 'color', 'academic_period_id',
            ])
        ));

        // Asignar color por defecto según el tipo si no se proporciona
        if (empty($this->color) && $this->type) {
            $this->merge([
                'color' => Event::TYPE_COLORS[$this->type] ?? null,
            ]);
        }
    }

    /**
     * Get the validated data with the created_by field.
     */
    public function validatedWithCreator(): array
    {
        return array_merge($this->validated(), [
            'created_by' => $this->user()->id,
        ]);
    }
}
