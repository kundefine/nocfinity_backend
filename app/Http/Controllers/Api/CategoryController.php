<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;

    // Storefront: Return hierarchical category tree
    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return $this->success($categories);
    }

    // Admin: List all categories with parent info
    public function adminIndex(Request $request)
    {
        $query = Category::with('parent');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('sort_order')->paginate($request->get('per_page', 20));

        return $this->paginated($categories);
    }

    // Admin: Create category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'image_url' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Ensure unique slug
        $count = Category::where('slug', 'like', "{$validated['slug']}%")->count();
        if ($count > 0) {
            $validated['slug'] .= '-' . ($count + 1);
        }

        $category = Category::create($validated);

        return $this->success($category, 'Category created successfully.', 201);
    }

    // Admin: Update category
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id', "not_in:{$id}"],
            'image_url' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        if ($validated['name'] !== $category->name) {
            $slug = Str::slug($validated['name']);
            $count = Category::where('slug', 'like', "{$slug}%")->where('id', '!=', $id)->count();
            $validated['slug'] = $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
        }

        $category->update($validated);

        return $this->success($category, 'Category updated successfully.');
    }

    // Admin: Delete category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->children()->exists()) {
            return $this->error('Cannot delete category with subcategories. Reassign or delete subcategories first.', 422);
        }

        if ($category->products()->exists()) {
            return $this->error('Cannot delete category with associated products.', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully.');
    }
}
