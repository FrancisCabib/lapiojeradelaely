<?php

namespace App\Http\Requests\Admin;

use App\Models\CatalogSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCatalogSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var CatalogSection $section */
        $section = $this->route('section');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique(CatalogSection::class)->ignore($section->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'weekday' => [
                'nullable',
                'integer',
                Rule::in([1, 2, 3, 4, 5, 6, 7]),
                Rule::unique('catalog_sections', 'weekday')->ignore($section->id),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $weekday = $this->input('weekday');
        if ($weekday === '' || $weekday === null) {
            $this->merge(['weekday' => null]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);

        if ($this->filled('name')) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
