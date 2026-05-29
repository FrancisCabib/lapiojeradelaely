<?php

namespace Database\Seeders;

use App\Models\CatalogSection;
use Illuminate\Database\Seeder;

class WeekdayMenuSectionsSeeder extends Seeder
{
    /**
     * Crea las 7 secciones de carta (Lunes → Domingo).
     * Para Desayuno / Almuerzo / Cena por día, ejecutá también WeekdayMenuCoursesSeeder y WeekdayMenuDishesSeeder.
     */
    public function run(): void
    {
        $days = [
            1 => ['name' => 'Lunes', 'slug' => 'lunes'],
            2 => ['name' => 'Martes', 'slug' => 'martes'],
            3 => ['name' => 'Miércoles', 'slug' => 'miercoles'],
            4 => ['name' => 'Jueves', 'slug' => 'jueves'],
            5 => ['name' => 'Viernes', 'slug' => 'viernes'],
            6 => ['name' => 'Sábado', 'slug' => 'sabado'],
            7 => ['name' => 'Domingo', 'slug' => 'domingo'],
        ];

        foreach ($days as $weekday => $data) {
            CatalogSection::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => 'Desayuno, almuerzo y cena. Tierra Amarilla, Región de Atacama.',
                    'is_active' => true,
                    'sort_order' => $weekday - 1,
                    'weekday' => $weekday,
                    'starts_at' => null,
                    'ends_at' => null,
                ]
            );
        }
    }
}
