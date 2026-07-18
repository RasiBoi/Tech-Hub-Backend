<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    protected ProductRepositoryInterface $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getFilteredProducts(array $filters): Collection
    {
        return $this->productRepository->getFilteredProducts($filters);
    }

    public function getProduct(int|string $id): ?Product
    {
        return $this->productRepository->find($id, ['*'], [
            'category', 
            'vendor' => function ($q) {
                $q->with('vendorSetting')
                  ->withCount(['followers', 'products'])
                  ->withAvg('products', 'rating');
            }
        ]);
    }

    public function createProduct(array $data, User $user): Product
    {
        return $this->productRepository->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'brand' => $data['brand'] ?? null,
            'subcategory' => $data['subcategory'] ?? null,
            'image' => $data['image'] ?? null,
            'images' => $data['images'] ?? null,
            'spec' => $data['spec'] ?? null,
            'stock' => $data['stock'] ?? 10,
            'rating' => 5.00,
            'reviews_count' => 0,
            'category_id' => $data['category_id'],
            'vendor_id' => $user->id,
            'vibe' => $data['vibe'] ?? null,
        ]);
    }

    public function updateProduct(int|string $id, array $data): bool
    {
        return $this->productRepository->update($id, $data);
    }

    public function deleteProduct(int|string $id): bool
    {
        return $this->productRepository->delete($id);
    }
}
