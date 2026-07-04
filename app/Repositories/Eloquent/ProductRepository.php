<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    /** Cache version key — incrementing this instantly invalidates all product list caches. */
    private const VERSION_KEY = 'products:cache:version';

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /** Get the current cache version, defaulting to 1. */
    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * Get filtered products with a short server-side cache.
     * Cache key is scoped to the filter combination so different filters cache independently.
     */
    public function getFilteredProducts(array $filters): Collection
    {
        $version  = self::version();
        $cacheKey = 'products:list:v' . $version . ':' . md5(serialize($filters));

        $products = Cache::remember($cacheKey, 60, function () use ($filters) {
            $query = $this->model->with([
                'category',
                'vendor' => function ($q) {
                    $q->with('vendorSetting')
                      ->withCount(['followers', 'products'])
                      ->withAvg('products', 'rating');
                }
            ]);

            // Filter by aesthetic vibe
            if (!empty($filters['vibe'])) {
                $query->where('vibe', $filters['vibe']);
            }

            // Filter by category slug or name
            if (!empty($filters['category'])) {
                $category = $filters['category'];
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category)
                      ->orWhere('name', $category);
                });
            }

            // Filter by vendor id
            if (!empty($filters['vendor_id'])) {
                $query->where('vendor_id', $filters['vendor_id']);
            }

            // Search in title/description
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            return $query->get();
        });

        if (!$products instanceof Collection) {
            Cache::forget($cacheKey);
            self::clearProductsCache();

            return $this->getFilteredProducts($filters);
        }

        return $products;
    }

    /**
     * Invalidate all products list caches by bumping the version counter.
     * This is O(1) and does NOT flush vendor, category, or any other caches.
     */
    public static function clearProductsCache(): void
    {
        $next = self::version() + 1;
        Cache::put(self::VERSION_KEY, $next, 3600);
    }
}
