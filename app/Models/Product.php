<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'details',
        'price',
        'discount_price',
        'stock',
        'category_id',
        'dress_style',
        'colors',
        'sizes',
        'is_active',
        'is_featured',
        'is_new_arrival',
        'is_top_selling',
    ];

    protected $appends = [
        'effective_price',
        'average_rating',
        'reviews_count',
        'in_stock',
        'stock_status',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'colors' => 'array',
            'sizes' => 'array',
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_top_selling' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->discount_price ?? $this->price);
    }

    public function getAverageRatingAttribute(): float
    {
        return round((float) $this->reviews()->avg('rating') ?: 4.5, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function getInStockAttribute(): bool
    {
        return (int) $this->stock > 0;
    }

    public function getStockStatusAttribute(): string
    {
        $stock = (int) $this->stock;
        if ($stock <= 0) {
            return 'out_of_stock';
        }
        if ($stock <= 5) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
