<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
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

    private function hydrateCachedProducts(array $rows): Collection
    {
        $products = new Collection();

        foreach ($rows as $row) {
            $category = $row['category'] ?? null;
            $vendor = $row['vendor'] ?? null;
            unset($row['category'], $row['vendor']);

            if (isset($row['images']) && is_array($row['images'])) {
                $row['images'] = json_encode($row['images']);
            }

            /** @var Product $product */
            $product = $this->model->newFromBuilder($row);

            if (is_array($category)) {
                $product->setRelation('category', (new Category())->newFromBuilder($category));
            }

            if (is_array($vendor)) {
                $vendorSetting = $vendor['vendor_setting'] ?? null;
                unset($vendor['vendor_setting']);

                /** @var User $vendorModel */
                $vendorModel = (new User())->newFromBuilder($vendor);
                if (is_array($vendorSetting)) {
                    $vendorModel->setRelation('vendorSetting', (new \App\Models\VendorSetting())->newFromBuilder($vendorSetting));
                }

                $product->setRelation('vendor', $vendorModel);
            }

            $products->push($product);
        }

        return $products;
    }

    /**
     * Get filtered products with server-side caching.
     *
     * CRITICAL FIX: We cache the raw toArray() output rather than the live
     * Eloquent Collection object. Storing Eloquent models in the file cache
     * can fail instanceof checks on deserialization (class-autoload timing),
     * which was triggering: instanceof fail → clearProductsCache() → recursive
     * call → fresh 9-second DB query on every single request, forever.
     */
    public function getFilteredProducts(array $filters): Collection
    {
        $version  = self::version();
        $cacheKey = 'products:list:v' . $version . ':' . md5(serialize($filters));

        $cached = Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = $this->model->with([
                // Only select columns needed for product cards to reduce payload size
                'category:id,name,slug,image',
                'vendor' => function ($q) {
                    $q->select([
                        'id', 'ai_uuid', 'name', 'email', 'role', 'store_name',
                        'avatar_bg', 'status', 'store_description', 'banner_url',
                    ]);
                },
            ]);

            if (!empty($filters['vibe'])) {
                $query->where('vibe', $filters['vibe']);
            }

            if (!empty($filters['category'])) {
                $category = $filters['category'];
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category)
                      ->orWhere('name', $category);
                });
            }

            if (!empty($filters['vendor_id'])) {
                $query->where('vendor_id', $filters['vendor_id']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Store as plain array — file-cache serialization of plain PHP arrays
            // is always safe regardless of class autoload order on retrieval.
            return $query->get()->toArray();
        });

        // Re-hydrate cached array back into Eloquent model instances so
        // ProductResource / UserResource continue to work unchanged.
        if (is_array($cached)) {
            return $this->hydrateCachedProducts($cached);
        }

        // Already a Collection (same-process warm hit before any restart).
        if ($cached instanceof Collection) {
            return $cached;
        }

        // Safety net: bust a corrupted entry and try once more.
        Cache::forget($cacheKey);
        return $this->getFilteredProducts($filters);
    }

    /**
     * Invalidate all products list caches by bumping the version counter.
     * O(1) — does NOT flush vendor, category, or any other caches.
     */
    public static function clearProductsCache(): void
    {
        $next = self::version() + 1;
        Cache::put(self::VERSION_KEY, $next, 3600);
    }
}
