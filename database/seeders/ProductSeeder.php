<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'title' => 'Laptop',
            'price' => 1000,
            'stock' => 100,
            'category_id' => 1,
        ]);

        Product::create([
            'title' => 'iphone',
            'price' => 500,
            'stock' => 1000,
            'category_id' => 1,
        ]);

        Product::create([
            'title' => 'Novel Book',
            'price' => 20,
            'stock' => 5000,
            'category_id' => 2,
        ]);

        Product::create([
            'title' => 'T-Shirt',
            'price' => 150,
            'stock' => 300,
            'category_id' => 3,
        ]);
    }
}