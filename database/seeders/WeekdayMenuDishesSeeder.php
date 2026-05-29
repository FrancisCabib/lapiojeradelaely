<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;

class WeekdayMenuDishesSeeder extends Seeder
{
    private const PRICE_DESAYUNO = 1500;

    private const PRICE_ALMUERZO_CENA = 3000;

    /**
     * Carga los platos de la carta semanal (hoja de la beneficiaria).
     * Re-ejecutable: deja un solo servicio activo por categoría día × comida.
     */
    public function run(): void
    {
        $this->call(WeekdayMenuCoursesSeeder::class);

        $grid = [
            'lunes' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, paté y té',
                    'description' => 'Pan con margarina, pan con paté, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Arroz con estofado',
                    'description' => 'Arroz con estofado, pan, 1 vaso de jugo (200 cc)',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Fideos con guiso de vienesa',
                    'description' => 'Fideos con guiso de vienesa, jugo, pan',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
            'martes' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, cecina y té',
                    'description' => 'Pan con margarina, pan con cecina, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Porotos',
                    'description' => 'Porotos, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Arroz con vienesa y tomate',
                    'description' => 'Arroz con vienesa y tomate, jugo, pan',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
            'miercoles' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, queso y té',
                    'description' => 'Pan con margarina, pan con queso, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Fideos con salsa',
                    'description' => 'Fideos con salsa, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Carbonada',
                    'description' => 'Carbonada, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
            'jueves' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, dulce y té',
                    'description' => 'Pan con margarina, pan con dulce, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Lentejas',
                    'description' => 'Lentejas, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Charquicán',
                    'description' => 'Charquicán, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
            'viernes' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, paté y té',
                    'description' => 'Pan con margarina, pan con paté, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Arroz con pollo',
                    'description' => 'Arroz con pollo, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Carbonada',
                    'description' => 'Carbonada, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
            'sabado' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, cecina y té',
                    'description' => 'Pan con margarina, pan con cecina, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Fideos con guiso',
                    'description' => 'Fideos con guiso, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Puré con hamburguesa',
                    'description' => 'Puré con hamburguesa al jugo, 1 vaso de jugo, pan',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
            'domingo' => [
                'desayuno' => [
                    'title' => 'Pan con margarina, queso y té',
                    'description' => 'Pan con margarina, pan con queso, té',
                    'price' => self::PRICE_DESAYUNO,
                ],
                'almuerzo' => [
                    'title' => 'Cazuela de vacuno',
                    'description' => 'Cazuela de vacuno, pan, 1 vaso de jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
                'cena' => [
                    'title' => 'Fideos con salsa de vienesas',
                    'description' => 'Fideos con salsa de vienesas, pan, jugo',
                    'price' => self::PRICE_ALMUERZO_CENA,
                ],
            ],
        ];

        foreach ($grid as $daySlug => $courses) {
            foreach ($courses as $courseSuffix => $dish) {
                $categorySlug = $daySlug.'-'.$courseSuffix;
                $category = Category::query()->where('slug', $categorySlug)->first();

                if ($category === null) {
                    continue;
                }

                Service::query()->where('category_id', $category->id)->delete();

                Service::query()->create([
                    'category_id' => $category->id,
                    'title' => $dish['title'],
                    'subtitle' => null,
                    'price' => $dish['price'],
                    'short_description' => $dish['description'],
                    'long_description' => null,
                    'image_path' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
        }
    }
}
