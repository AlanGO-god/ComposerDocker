<?php
declare(strict_types=1);

namespace App;

use Faker\Factory;

final class DataGenerator
{
    /** Devuelve una lista de productos de ejemplo. */
    public static function productos(int $cantidad = 10): array
    {
        $faker = Factory::create('es_MX');
        $catalogo = ['Laptop', 'Mouse', 'Teclado', 'Monitor', 'Audífonos', 'Webcam', 'Disco SSD', 'Memoria USB'];

        $filas = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $filas[] = [
                'id'       => $i,
                'producto' => $faker->randomElement($catalogo),
                'cantidad' => $faker->numberBetween(1, 20),
                'precio'   => $faker->randomFloat(2, 150, 9000),
            ];
        }
        return $filas;
    }
}