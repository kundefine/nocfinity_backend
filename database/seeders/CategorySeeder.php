<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Men',
                'description' => 'Men apparel, streetwear, casual and formal collections.',
                'subcategories' => ['T-shirts', 'Shirts', 'Jeans', 'Shorts', 'Hoodies'],
            ],
            [
                'name' => 'Women',
                'description' => 'Women fashion, dresses, contemporary streetwear and denim.',
                'subcategories' => ['Dresses', 'Tops', 'Jeans', 'Skirts'],
            ],
            [
                'name' => 'Accessories',
                'description' => 'Belts, bags, caps, and lifestyle gear.',
                'subcategories' => ['Belts', 'Bags', 'Hats'],
            ],
        ];

        foreach ($categories as $idx => $cat) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'parent_id' => null,
                    'is_active' => true,
                    'sort_order' => $idx + 1,
                ]
            );

            foreach ($cat['subcategories'] as $sIdx => $subName) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($parent->name . '-' . $subName)],
                    [
                        'name' => $subName,
                        'description' => "{$subName} for {$parent->name}",
                        'parent_id' => $parent->id,
                        'is_active' => true,
                        'sort_order' => $sIdx + 1,
                    ]
                );
            }
        }
    }
}
