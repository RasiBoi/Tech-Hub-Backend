<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Traits\ApiResponse;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $totalAmount = 0;
            $itemsToCreate = [];

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);

                if ($product->stock < $itemData['quantity']) {
                    return $this->sendError(
                        "Insufficient stock for product: {$product->title}. Available: {$product->stock}",
                        422
                    );
                }

                $price = $product->price;
                $quantity = $itemData['quantity'];
                $totalAmount += $price * $quantity;

                $product->decrement('stock', $quantity);

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'price' => $price,
                    'quantity' => $quantity,
                ];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'completed',
            ]);

            foreach ($itemsToCreate as $item) {
                $order->items()->create($item);
            }

            $order->load(['items.product', 'user']);

            return $this->sendSuccess(new OrderResource($order), 'Order placed successfully', 201);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $orders = Order::with(['user', 'items.product'])->latest()->get();
            return $this->sendSuccess(OrderResource::collection($orders), 'All orders retrieved successfully');
        }

        if ($user->role === 'vendor') {
            $orderItems = OrderItem::whereHas('product', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            })->with(['order.user', 'product'])->latest()->get();

            return $this->sendSuccess(OrderItemResource::collection($orderItems), 'Vendor order items retrieved');
        }

        $orders = Order::where('user_id', $user->id)->with('items.product')->latest()->get();
        return $this->sendSuccess(OrderResource::collection($orders), 'Customer orders retrieved');
    }
}
