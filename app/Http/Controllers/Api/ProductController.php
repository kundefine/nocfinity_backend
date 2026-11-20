<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;

    // Storefront: List products with filters and sorting
    public function index(Request $request)
    {
        $query = Product::active()->with(['category', 'images']);

        // Search query (case-insensitive across PostgreSQL & MySQL)
        if ($request->filled('q') || $request->filled('search')) {
            $search = $request->get('q', $request->get('search'));
            $lower = '%' . mb_strtolower(trim($search)) . '%';
            $query->where(function ($q) use ($lower) {
                $q->whereRaw('LOWER(name) LIKE ?', [$lower])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$lower])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$lower]);
            });
        }

        // Category filter (accepts slug or id)
        if ($request->filled('category')) {
            $cat = $request->category;
            $query->whereHas('category', function ($q) use ($cat) {
                if (is_numeric($cat)) {
                    $q->where('id', (int) $cat)->orWhere('slug', $cat);
                } else {
                    $q->where('slug', $cat);
                }
            });
        }

        // Dress Style filter
        if ($request->filled('style') || $request->filled('dress_style')) {
            $style = $request->get('style', $request->get('dress_style'));
            $query->where('dress_style', $style);
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Color variant filter (supports PostgreSQL and MySQL)
        if ($request->filled('color')) {
            $color = mb_strtolower(trim($request->color));
            $likePattern = '%' . $color . '%';
            $query->where(function ($q) use ($likePattern, $color) {
                $rawCol = config('database.default') === 'pgsql' ? 'LOWER(colors::text)' : 'LOWER(CAST(colors AS CHAR))';
                $q->whereRaw("$rawCol LIKE ?", [$likePattern]);
                if ($color === 'blue') {
                    $q->orWhereRaw("$rawCol LIKE ?", ['%navy%'])
                      ->orWhereRaw("$rawCol LIKE ?", ['%denim%']);
                } elseif ($color === 'green') {
                    $q->orWhereRaw("$rawCol LIKE ?", ['%olive%']);
                } elseif ($color === 'black') {
                    $q->orWhereRaw("$rawCol LIKE ?", ['%charcoal%']);
                } elseif ($color === 'red') {
                    $q->orWhereRaw("$rawCol LIKE ?", ['%maroon%']);
                }
            });
        }

        // Size variant filter (supports PostgreSQL and MySQL)
        if ($request->filled('size')) {
            $size = mb_strtolower(trim($request->size));
            $likePattern = '%' . $size . '%';
            $query->where(function ($q) use ($likePattern, $size) {
                $rawCol = config('database.default') === 'pgsql' ? 'LOWER(sizes::text)' : 'LOWER(CAST(sizes AS CHAR))';
                $q->whereRaw("$rawCol LIKE ?", [$likePattern]);
                $sizeMap = [
                    's' => 'small',
                    'm' => 'medium',
                    'l' => 'large',
                    'xl' => 'x-large',
                    'xxl' => '2x-large',
                ];
                if (isset($sizeMap[$size])) {
                    $q->orWhereRaw("$rawCol LIKE ?", ['%' . $sizeMap[$size] . '%']);
                }
            });
        }

        // Stock and availability filters
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        } elseif ($request->filled('stock_status')) {
            $status = $request->stock_status;
            if ($status === 'in_stock') {
                $query->where('stock', '>', 0);
            } elseif ($status === 'low_stock' || $status === 'low') {
                $query->where('stock', '>', 0)->where('stock', '<=', 5);
            } elseif ($status === 'out_of_stock' || $status === 'out') {
                $query->where('stock', '<=', 0);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
            case 'price-low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
            case 'price-high':
                $query->orderBy('price', 'desc');
                break;
            case 'top_selling':
            case 'popular':
                $query->orderBy('is_top_selling', 'desc')->latest();
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $perPage = (int) $request->get('per_page', 12);
        $products = $query->paginate($perPage);

        return $this->paginated($products);
    }

    // Storefront: Return featured sections (New Arrivals, Top Selling, Dress Styles, Customer Reviews)
    public function featured()
    {
        $newArrivals = Product::active()
            ->with(['category', 'images'])
            ->where('is_new_arrival', true)
            ->take(8)
            ->get();

        $topSelling = Product::active()
            ->with(['category', 'images'])
            ->where('is_top_selling', true)
            ->take(8)
            ->get();

        $dressStyles = [
            [
                'name' => 'Casual',
                'slug' => 'casual',
                'href' => '/shop?style=casual',
                'image' => '/figma/imgImage15_8433b976.png',
                'col_span' => 'lg:col-span-4',
                'products_count' => Product::active()->where('dress_style', 'casual')->count(),
            ],
            [
                'name' => 'Formal',
                'slug' => 'formal',
                'href' => '/shop?style=formal',
                'image' => '/figma/imgImage16_f8900dc6.png',
                'col_span' => 'lg:col-span-8',
                'products_count' => Product::active()->where('dress_style', 'formal')->count(),
            ],
            [
                'name' => 'Party',
                'slug' => 'party',
                'href' => '/shop?style=party',
                'image' => '/figma/imgImage17_3c5517f0.png',
                'col_span' => 'lg:col-span-8',
                'products_count' => Product::active()->where('dress_style', 'party')->count(),
            ],
            [
                'name' => 'Gym',
                'slug' => 'gym',
                'href' => '/shop?style=gym',
                'image' => '/figma/imgImage18_ed57d863.png',
                'col_span' => 'lg:col-span-4',
                'products_count' => Product::active()->where('dress_style', 'gym')->count(),
            ],
        ];

        $customerReviews = ProductReview::with('user')
            ->where('rating', '>=', 4)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($rev) {
                return [
                    'id' => (string) $rev->id,
                    'author' => $rev->user?->name ?? 'Verified Buyer',
                    'verified' => (bool) $rev->verified_purchase,
                    'rating' => (int) $rev->rating,
                    'comment' => $rev->comment,
                    'date' => $rev->created_at ? 'Posted on ' . $rev->created_at->format('F d, Y') : 'Recent',
                ];
            });

        return $this->success([
            'new_arrivals' => $newArrivals,
            'top_selling' => $topSelling,
            'dress_styles' => $dressStyles,
            'customer_reviews' => $customerReviews,
        ]);
    }

    // Storefront: Single product details with related products
    public function show($idOrSlug)
    {
        $product = Product::active()
            ->with(['category', 'images', 'reviews.user'])
            ->where(function ($q) use ($idOrSlug) {
                if (is_numeric($idOrSlug)) {
                    $q->where('id', (int) $idOrSlug)->orWhere('slug', $idOrSlug);
                } else {
                    $q->where('slug', $idOrSlug);
                }
            })
            ->firstOrFail();

        $related = Product::active()
            ->with(['category', 'images'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return $this->success([
            'product' => $product,
            'related' => $related,
        ]);
    }

    // Storefront: Add review
    public function addReview(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        // Check if user purchased the product
        $verified = $user->orders()
            ->where('status', '!=', 'cancelled')
            ->whereHas('items', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })->exists();

        $review = ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'verified_purchase' => $verified,
        ]);

        return $this->success($review->load('user'), 'Review submitted successfully.', 201);
    }

    // Admin: List all products
    public function adminIndex(Request $request)
    {
        $query = Product::with(['category', 'images']);

        if ($request->filled('search')) {
            $s = '%' . mb_strtolower(trim($request->search)) . '%';
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(name) LIKE ?', [$s])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$s]);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->where('stock', '<=', 5);
            } elseif ($request->stock_status === 'out') {
                $query->where('stock', '<=', 0);
            }
        }

        $products = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginated($products);
    }

    // Admin: Store product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'dress_style' => ['nullable', 'in:casual,formal,party,gym'],
            'colors' => ['nullable', 'array'],
            'sizes' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_new_arrival' => ['boolean'],
            'is_top_selling' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*.image_url' => ['required_with:images', 'string'],
            'images.*.is_primary' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $count = Product::where('slug', 'like', "{$validated['slug']}%")->count();
        if ($count > 0) {
            $validated['slug'] .= '-' . ($count + 1);
        }

        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $product = Product::create($validated);

        if (!empty($images)) {
            foreach ($images as $idx => $img) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $img['image_url'],
                    'is_primary' => $img['is_primary'] ?? ($idx === 0),
                    'sort_order' => $idx,
                ]);
            }
        }

        return $this->success($product->load(['category', 'images']), 'Product created successfully.', 201);
    }

    // Admin: Update product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', "unique:products,sku,{$id}"],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'dress_style' => ['nullable', 'in:casual,formal,party,gym'],
            'colors' => ['nullable', 'array'],
            'sizes' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'images.*.image_url' => ['required_with:images', 'string'],
            'images.*.is_primary' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_new_arrival' => ['boolean'],
            'is_top_selling' => ['boolean'],
        ]);

        if ($validated['name'] !== $product->name) {
            $slug = Str::slug($validated['name']);
            $count = Product::where('slug', 'like', "{$slug}%")->where('id', '!=', $id)->count();
            $validated['slug'] = $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
        }

        $imagesData = $validated['images'] ?? null;
        unset($validated['images']);

        $product->update($validated);

        if ($imagesData !== null) {
            $product->images()->delete();
            foreach ($imagesData as $idx => $img) {
                if (empty($img['image_url'])) continue;
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $img['image_url'],
                    'is_primary' => $img['is_primary'] ?? ($idx === 0),
                    'sort_order' => $idx,
                ]);
            }
        }

        return $this->success($product->load(['category', 'images']), 'Product updated successfully.');
    }

    // Admin: Delete product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return $this->success(null, 'Product deleted successfully.');
    }
}
