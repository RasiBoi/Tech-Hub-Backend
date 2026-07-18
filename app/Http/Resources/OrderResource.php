<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ai_order_id' => $this->ai_order_id,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'vendor_id' => $this->vendor_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'ai_items' => $this->getRawOriginal('items') ? json_decode($this->getRawOriginal('items'), true) : null,
            'shipping_name' => $this->shipping_name,
            'shipping_phone' => $this->shipping_phone,
            'shipping_address' => $this->shipping_address,
            'tracking_number' => $this->tracking_number,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'purchase_date' => $this->purchase_date?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
