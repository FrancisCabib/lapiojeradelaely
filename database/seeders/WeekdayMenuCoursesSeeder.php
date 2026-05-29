<?php

namespace Database\Seeders;

use App\Models\CatalogSection;
use App\Models\Category;
use Illuminate\Database\Seeder;

class WeekdayMenuCoursesSeeder extends Seeder
{
    /**
     * Por cada día (Lunes–Domingo): crea 3 categorías (Desayuno, Almuerzo, Cena)
     * y las enlaza a la sección en ese orden.
     */
    public function run(): void
    {
        $this->call(WeekdayMenuSectionsSeeder::class);

        $days = [
            1 => ['name' => 'Lunes', 'slug' => 'lunes'],
            2 => ['name' => 'Martes', 'slug' => 'martes'],
            3 => ['name' => 'Miércoles', 'slug' => 'miercoles'],
            4 => ['name' => 'Jueves', 'slug' => 'jueves'],
            5 => ['name' => 'Viernes', 'slug' => 'viernes'],
            6 => ['name' => 'Sábado', 'slug' => 'sabado'],
            7 => ['name' => 'Domingo', 'slug' => 'domingo'],
        ];

        $courses = [
            ['suffix' => 'desayuno', 'heading' => 'Desayuno', 'course' => 'desayuno'],
            ['suffix' => 'almuerzo', 'heading' => 'Almuerzo', 'course' => 'almuerzo'],
            ['suffix' => 'cena', 'heading' => 'Cena', 'course' => 'cena'],
        ];

        foreach ($days as $data) {
            $section = CatalogSection::query()->where('slug', $data['slug'])->first();

            if ($section === null) {
                continue;
            }

            $sync = [];

            foreach ($courses as $index => $course) {
                $slug = $data['slug'].'-'.$course['suffix'];
                $uniqueName = $data['name'].' · '.$course['heading'];

                $category = Category::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $uniqueName,
                        'slug' => $slug,
                        'menu_heading' => $course['heading'],
                        'menu_course' => $course['course'],
                        'sort_order' => $index,
                        'is_active' => true,
                    ]
                );

                $sync[$category->id] = ['sort_order' => $index];
            }

            $section->categories()->sync($sync);
        }
    }
}
