<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class)->ignore($category->id)],
            'menu_heading' => ['nullable', 'string', 'max:100'],
            'menu_course' => ['nullable', 'string', 'max:32', 'in:entrada,plato_fondo,bebestibles'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('menu_course') === '') {
            $this->merge(['menu_course' => null]);
        }

        if ($this->input('menu_heading') === '') {
            $this->merge(['menu_heading' => null]);
        }

        if ($this->filled('name')) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
