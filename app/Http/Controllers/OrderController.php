<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Traits\ApiResponse;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|max:255',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_phone' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $totalAmount = 0;
            $itemsToCreate = [];
            $itemSnapshots = [];
            $vendorIds = [];

            $customer = Customer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id' => $user->ai_uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tier' => 'standard',
                ]
            );

            foreach ($request->items as $itemData) {
                $product = Product::with('vendor')->findOrFail($itemData['product_id']);

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

                $vendorUuid = $product->vendor?->ai_uuid;
                if ($vendorUuid) {
                    $vendorIds[] = $vendorUuid;
                }

                $itemSnapshots[] = [
                    'product_id' => $product->id,
                    'name' => $product->title,
                    'qty' => $quantity,
                    'price' => (float) $price,
                    'vendor_id' => $vendorUuid,
                ];
            }

            $uniqueVendorIds = array_values(array_unique(array_filter($vendorIds)));

            $order = Order::create([
                'ai_order_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'vendor_id' => count($uniqueVendorIds) === 1 ? $uniqueVendorIds[0] : null,
                'total_amount' => $totalAmount,
                'currency' => 'LKR',
                'items' => $itemSnapshots,
                'shipping_name' => $request->input('shipping_name'),
                'shipping_phone' => $request->input('shipping_phone'),
                'shipping_address' => $request->input('shipping_address'),
                'payment_method' => $request->input('payment_method', 'Demo Payment'),
                'purchase_date' => now(),
                'status' => 'processing',
            ]);

            $order->update([
                'order_number' => 'TH-' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT),
            ]);

            foreach ($itemsToCreate as $item) {
                $order->orderItems()->create($item);
            }

            $customer->update([
                'total_orders' => Order::where('customer_id', $customer->id)->count(),
            ]);

            $order->load(['orderItems.product', 'user']);

            return $this->sendSuccess(new OrderResource($order), 'Order placed successfully', 201);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $orders = Order::with(['user', 'orderItems.product'])->latest()->get();
            return $this->sendSuccess(OrderResource::collection($orders), 'All orders retrieved successfully');
        }

        if ($user->role === 'vendor') {
            $orderItems = OrderItem::whereHas('product', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            })->with(['order.user', 'product'])->latest()->get();

            return $this->sendSuccess(OrderItemResource::collection($orderItems), 'Vendor order items retrieved');
        }

        $orders = Order::where('user_id', $user->id)->with('orderItems.product')->latest()->get();
        return $this->sendSuccess(OrderResource::collection($orders), 'Customer orders retrieved');
    }

    public function dispatchItem(Request $request, $id): JsonResponse
    {
        $request->validate([
            'courier_name' => 'required|string|max:255',
            'tracking_code' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $orderItem = OrderItem::with('product')->findOrFail($id);

        if ($user->role !== 'admin' && $orderItem->product->vendor_id !== $user->id) {
            return $this->sendError('This action is unauthorized.', 403);
        }

        $orderItem->update([
            'status' => 'dispatched',
            'courier_name' => $request->input('courier_name'),
            'tracking_code' => $request->input('tracking_code'),
        ]);

        // If all items in this order are dispatched, update the main order status as well
        $order = $orderItem->order;
        if ($order) {
            $allDispatched = !OrderItem::where('order_id', $order->id)
                ->where('status', '!=', 'dispatched')
                ->exists();
            if ($allDispatched) {
                $trackingCodes = OrderItem::where('order_id', $order->id)
                    ->whereNotNull('tracking_code')
                    ->pluck('tracking_code')
                    ->unique()
                    ->values()
                    ->all();

                $order->update([
                    'status' => 'dispatched',
                    'tracking_number' => implode(', ', $trackingCodes),
                ]);
            }
        }

        return $this->sendSuccess(new OrderItemResource($orderItem), 'Order item dispatched successfully');
    }
}
