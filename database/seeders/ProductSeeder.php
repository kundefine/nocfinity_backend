<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tshirtsCat = Category::where('name', 'T-shirts')->first() ?? Category::first();
        $jeansCat = Category::where('name', 'Jeans')->first() ?? Category::first();
        $shirtsCat = Category::where('name', 'Shirts')->first() ?? Category::first();
        $shortsCat = Category::where('name', 'Shorts')->first() ?? Category::first();
        $hoodiesCat = Category::where('name', 'Hoodies')->first() ?? Category::first();
        $accessoriesCat = Category::where('name', 'Belts')->first() ?? Category::first();

        $customer = User::where('email', 'customer@shop.co')->first();

        $productList = [
            [
                'name' => 'T-shirt with Tape Details',
                'sku' => 'TSH-001',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'casual',
                'price' => 120.00,
                'discount_price' => null,
                'stock' => 45,
                'is_new_arrival' => true,
                'is_top_selling' => false,
                'description' => 'This graphic t-shirt which is perfect for any occasion. Crafted from a soft and breathable fabric, it offers superior comfort and style.',
                'colors' => [['name' => 'Brown', 'hex' => '#4F4631'], ['name' => 'Green', 'hex' => '#314F4A']],
                'sizes' => ['Small', 'Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage7_8ad0bca2.png', '/figma/imgImage5_692d8673.png'],
            ],
            [
                'name' => 'Skinny Fit Jeans',
                'sku' => 'JEA-002',
                'category_id' => $jeansCat->id,
                'dress_style' => 'casual',
                'price' => 260.00,
                'discount_price' => 240.00,
                'stock' => 30,
                'is_new_arrival' => true,
                'is_top_selling' => false,
                'description' => 'Classic skinny fit jeans tailored for maximum comfort and style with flexible premium denim.',
                'colors' => [['name' => 'Denim Blue', 'hex' => '#2B3D52'], ['name' => 'Black', 'hex' => '#1C1C1E']],
                'sizes' => ['Small', 'Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage8_e99f7761.png'],
            ],
            [
                'name' => 'Checkered Shirt',
                'sku' => 'SHT-003',
                'category_id' => $shirtsCat->id,
                'dress_style' => 'casual',
                'price' => 180.00,
                'discount_price' => null,
                'stock' => 25,
                'is_new_arrival' => true,
                'is_top_selling' => false,
                'description' => 'A stylish checkered pattern button-up shirt crafted with lightweight cotton for effortless everyday style.',
                'colors' => [['name' => 'Red Check', 'hex' => '#8B263E']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage9_d39ca11e.png'],
            ],
            [
                'name' => 'Sleeve Striped T-shirt',
                'sku' => 'TSH-004',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'casual',
                'price' => 160.00,
                'discount_price' => 130.00,
                'stock' => 15,
                'is_new_arrival' => true,
                'is_top_selling' => false,
                'description' => 'Contrast sleeve striped t-shirt delivering a modern sporty vibe with everyday comfort.',
                'colors' => [['name' => 'Orange', 'hex' => '#E56B3A'], ['name' => 'Navy', 'hex' => '#223843']],
                'sizes' => ['Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage10_319b7e46.png'],
            ],
            [
                'name' => 'Vertical Striped Shirt',
                'sku' => 'SHT-005',
                'category_id' => $shirtsCat->id,
                'dress_style' => 'formal',
                'price' => 232.00,
                'discount_price' => 212.00,
                'stock' => 40,
                'is_new_arrival' => false,
                'is_top_selling' => true,
                'description' => 'Sharp vertical striped casual shirt that elongates the silhouette, suitable for both business casual and weekend events.',
                'colors' => [['name' => 'Green Striped', 'hex' => '#315243']],
                'sizes' => ['Small', 'Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage11_2351479d.png'],
            ],
            [
                'name' => 'Courage Graphic T-shirt',
                'sku' => 'TSH-006',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'casual',
                'price' => 145.00,
                'discount_price' => null,
                'stock' => 60,
                'is_new_arrival' => false,
                'is_top_selling' => true,
                'description' => 'Bold statement typography print on premium combed cotton. Built to last wash after wash.',
                'colors' => [['name' => 'Orange', 'hex' => '#C85A32']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage12_98e907e6.png'],
            ],
            [
                'name' => 'Loose Fit Bermuda Shorts',
                'sku' => 'SHT-007',
                'category_id' => $shortsCat->id,
                'dress_style' => 'casual',
                'price' => 80.00,
                'discount_price' => null,
                'stock' => 18,
                'is_new_arrival' => false,
                'is_top_selling' => true,
                'description' => 'Breezy and relaxed Bermuda cut shorts with deep pockets and an elastic drawstring waist.',
                'colors' => [['name' => 'Navy', 'hex' => '#273C4E']],
                'sizes' => ['Medium', 'Large'],
                'images' => ['/figma/imgImage13_80b3495a.png'],
            ],
            [
                'name' => 'Faded Skinny Jeans',
                'sku' => 'JEA-008',
                'category_id' => $jeansCat->id,
                'dress_style' => 'casual',
                'price' => 210.00,
                'discount_price' => null,
                'stock' => 22,
                'is_new_arrival' => false,
                'is_top_selling' => true,
                'description' => 'Artfully faded wash denim jeans designed with stretch fibers for maximum freedom of motion.',
                'colors' => [['name' => 'Light Blue', 'hex' => '#58738D']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage14_703bf2aa.png'],
            ],
            [
                'name' => 'One Life Graphic T-shirt',
                'sku' => 'TSH-009',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'casual',
                'price' => 300.00,
                'discount_price' => 260.00,
                'stock' => 50,
                'is_new_arrival' => true,
                'is_top_selling' => false,
                'description' => 'This graphic t-shirt which is perfect for any occasion. Crafted from a soft and breathable fabric, it offers superior comfort and style.',
                'colors' => [['name' => 'Olive Green', 'hex' => '#4F4631'], ['name' => 'Forest Green', 'hex' => '#314F4A'], ['name' => 'Navy', 'hex' => '#31344F']],
                'sizes' => ['Small', 'Medium', 'Large', 'X-Large'],
                'images' => [
                    '/figma/imgImage1_99e98133.png',
                    '/figma/imgImage5_692d8673.png',
                    '/figma/imgImage6_81b2425e.png'
                ],
            ],
            [
                'name' => 'Polo with Tipping Details',
                'sku' => 'POL-010',
                'category_id' => $shirtsCat->id,
                'dress_style' => 'formal',
                'price' => 180.00,
                'discount_price' => null,
                'stock' => 35,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Elevated polo shirt with tipped rib collar and sleeve cuffs. Crisp and modern silhouette.',
                'colors' => [['name' => 'Pink', 'hex' => '#E07A5F']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage9_b0eef762.png'],
            ],
            [
                'name' => 'Black Striped T-shirt',
                'sku' => 'TSH-011',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'casual',
                'price' => 150.00,
                'discount_price' => 120.00,
                'stock' => 12,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Timeless horizontal striped tee in monochrome black and white. Ultra-soft combed cotton.',
                'colors' => [['name' => 'Black Striped', 'hex' => '#1C1C1E']],
                'sizes' => ['Small', 'Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage10_51859230.png'],
            ],
            [
                'name' => 'Gradient Graphic T-shirt',
                'sku' => 'TSH-012',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'party',
                'price' => 145.00,
                'discount_price' => null,
                'stock' => 28,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Vibrant gradient graphic artwork on a comfortable, relaxed-fit streetwear staple.',
                'colors' => [['name' => 'Multi', 'hex' => '#E07A5F']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage8_30fc2006.png'],
            ],
            [
                'name' => 'Classic White Crewneck',
                'sku' => 'TSH-013',
                'category_id' => $tshirtsCat->id,
                'dress_style' => 'casual',
                'price' => 45.00,
                'discount_price' => null,
                'stock' => 80,
                'is_new_arrival' => false,
                'is_top_selling' => true,
                'description' => 'Essential heavyweight white crewneck tee crafted from 100% organic combed cotton.',
                'colors' => [['name' => 'White', 'hex' => '#FFFFFF']],
                'sizes' => ['Small', 'Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage7_8ad0bca2.png'],
            ],
            [
                'name' => 'Slim Fit Oxford Shirt',
                'sku' => 'SHT-014',
                'category_id' => $shirtsCat->id,
                'dress_style' => 'formal',
                'price' => 110.00,
                'discount_price' => null,
                'stock' => 20,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Crisp button-down collar tailored oxford cloth shirt ideal for work or social settings.',
                'colors' => [['name' => 'Light Blue', 'hex' => '#58738D']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage11_2351479d.png'],
            ],
            [
                'name' => 'Ripped Streetwear Jeans',
                'sku' => 'JEA-015',
                'category_id' => $jeansCat->id,
                'dress_style' => 'casual',
                'price' => 195.00,
                'discount_price' => null,
                'stock' => 14,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Distressed knee rips with raw hems for that authentic vintage street-style aesthetic.',
                'colors' => [['name' => 'Washed Black', 'hex' => '#2B2B2B']],
                'sizes' => ['Small', 'Medium', 'Large'],
                'images' => ['/figma/imgImage8_e99f7761.png'],
            ],
            [
                'name' => 'Heavyweight Pullover Hoodie',
                'sku' => 'HOD-016',
                'category_id' => $hoodiesCat->id,
                'dress_style' => 'casual',
                'price' => 165.00,
                'discount_price' => null,
                'stock' => 32,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => '450 GSM fleece hoodie with double-lined hood and sturdy kangaroo pocket.',
                'colors' => [['name' => 'Oatmeal', 'hex' => '#D8D4D0'], ['name' => 'Black', 'hex' => '#000000']],
                'sizes' => ['Medium', 'Large', 'X-Large'],
                'images' => ['/figma/imgImage12_98e907e6.png'],
            ],
            [
                'name' => 'Relaxed Linen Shirt',
                'sku' => 'SHT-017',
                'category_id' => $shirtsCat->id,
                'dress_style' => 'party',
                'price' => 135.00,
                'discount_price' => null,
                'stock' => 4, // Low stock demo!
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Breathable pure European flax linen button-up designed for coastal and warm-weather wear.',
                'colors' => [['name' => 'Sand', 'hex' => '#E5DDD4']],
                'sizes' => ['Medium', 'Large'],
                'images' => ['/figma/imgImage9_d39ca11e.png'],
            ],
            [
                'name' => 'Graphic Skater Hoodie',
                'sku' => 'HOD-018',
                'category_id' => $hoodiesCat->id,
                'dress_style' => 'casual',
                'price' => 175.00,
                'discount_price' => null,
                'stock' => 2, // Low stock demo!
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Limited edition back screen-printed graphic hoodie with drop-shoulder cut.',
                'colors' => [['name' => 'Forest Green', 'hex' => '#314F4A']],
                'sizes' => ['Large', 'X-Large'],
                'images' => ['/figma/imgImage10_319b7e46.png'],
            ],
            [
                'name' => 'Cargo Utility Pants',
                'sku' => 'PNT-019',
                'category_id' => $jeansCat->id,
                'dress_style' => 'gym',
                'price' => 150.00,
                'discount_price' => null,
                'stock' => 0, // Out of stock demo!
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Ripstop durable cargo trousers with multiple utilitarian bellows pockets.',
                'colors' => [['name' => 'Olive', 'hex' => '#4B5320']],
                'sizes' => ['Medium', 'Large'],
                'images' => ['/figma/imgImage14_703bf2aa.png'],
            ],
            [
                'name' => 'Premium Leather Belt',
                'sku' => 'ACC-020',
                'category_id' => $accessoriesCat->id,
                'dress_style' => 'formal',
                'price' => 75.00,
                'discount_price' => null,
                'stock' => 50,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Full-grain vegetable tanned Italian leather belt with solid brass buckle.',
                'colors' => [['name' => 'Brown', 'hex' => '#654321'], ['name' => 'Black', 'hex' => '#000000']],
                'sizes' => ['Medium', 'Large'],
                'images' => ['/figma/imgImage13_80b3495a.png'],
            ],
            [
                'name' => 'Minimalist Canvas Tote',
                'sku' => 'ACC-021',
                'category_id' => $accessoriesCat->id,
                'dress_style' => 'casual',
                'price' => 55.00,
                'discount_price' => null,
                'stock' => 40,
                'is_new_arrival' => false,
                'is_top_selling' => false,
                'description' => 'Heavy duty 16oz cotton canvas everyday tote with reinforced handles.',
                'colors' => [['name' => 'Natural', 'hex' => '#FAF0E6']],
                'sizes' => ['One Size'],
                'images' => ['/figma/imgImage12_98e907e6.png'],
            ],
        ];

        foreach ($productList as $pData) {
            $images = $pData['images'];
            unset($pData['images']);

            $slug = Str::slug($pData['name']);
            $product = Product::firstOrCreate(
                ['sku' => $pData['sku']],
                array_merge($pData, ['slug' => $slug])
            );

            // Add images
            foreach ($images as $idx => $imgUrl) {
                ProductImage::firstOrCreate(
                    ['product_id' => $product->id, 'image_url' => $imgUrl],
                    ['is_primary' => $idx === 0, 'sort_order' => $idx]
                );
            }

            // Add reviews if customer exists
            if ($customer && $product->reviews()->count() === 0) {
                ProductReview::create([
                    'product_id' => $product->id,
                    'user_id' => $customer->id,
                    'rating' => 5,
                    'comment' => "I'm blown away by the quality and craftsmanship of this item! Fits true to size and feels very comfortable.",
                    'verified_purchase' => true,
                ]);
            }
        }

        // Seed dedicated customer reviews for homepage & testimonials
        $featuredReviews = [
            [
                'name' => 'Sarah M.',
                'email' => 'sarah.m@example.com',
                'rating' => 5,
                'comment' => "I'm blown away by the quality and style of the clothes I received from Shop.co. From casual wear to elegant dresses, every piece I've bought has exceeded my expectations.",
            ],
            [
                'name' => 'Alex K.',
                'email' => 'alex.k@example.com',
                'rating' => 5,
                'comment' => "Finding clothes that align with my personal style used to be a challenge until I discovered Shop.co. The range of options they offer is truly remarkable, catering to a variety of tastes and occasions.",
            ],
            [
                'name' => 'James L.',
                'email' => 'james.l@example.com',
                'rating' => 5,
                'comment' => "As someone who's always on the lookout for unique fashion pieces, I'm thrilled to have stumbled upon Shop.co. The selection of clothes is not only diverse but also on-point with the latest trends.",
            ],
            [
                'name' => 'Samantha D.',
                'email' => 'samantha.d@example.com',
                'rating' => 5,
                'comment' => "The craftsmanship and fabric weight are incredible for the price point. Fast shipping and the customer service team answered all my questions within minutes.",
            ],
            [
                'name' => 'Ethan R.',
                'email' => 'ethan.r@example.com',
                'rating' => 5,
                'comment' => "This has become my favorite store. Everything fits true to size and washes without shrinking or losing color.",
            ],
            [
                'name' => 'Olivia P.',
                'email' => 'olivia.p@example.com',
                'rating' => 5,
                'comment' => "Stunning pieces! I wore this to an evening dinner and received so many compliments. Highly recommended!",
            ],
        ];

        $firstProduct = Product::first();
        if ($firstProduct) {
            foreach ($featuredReviews as $rData) {
                $user = User::firstOrCreate(
                    ['email' => $rData['email']],
                    [
                        'name' => $rData['name'],
                        'password' => bcrypt('password123'),
                        'is_active' => true,
                    ]
                );

                ProductReview::firstOrCreate(
                    [
                        'product_id' => $firstProduct->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'rating' => $rData['rating'],
                        'comment' => $rData['comment'],
                        'verified_purchase' => true,
                    ]
                );
            }
        }
    }
}
