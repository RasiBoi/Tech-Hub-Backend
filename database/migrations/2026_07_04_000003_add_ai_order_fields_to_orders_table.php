<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'ai_order_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->uuid('ai_order_id')->nullable()->unique()->after('id');
                $table->text('order_number')->nullable()->unique()->after('ai_order_id');
                $table->uuid('customer_id')->nullable()->after('user_id');
                $table->uuid('vendor_id')->nullable()->after('customer_id');
                $table->text('currency')->default('LKR')->after('total_amount');
                $table->jsonb('items')->nullable()->after('currency');
                $table->text('shipping_address')->nullable()->after('items');
                $table->text('shipping_name')->nullable()->after('shipping_address');
                $table->text('shipping_phone')->nullable()->after('shipping_name');
                $table->text('tracking_number')->nullable()->after('shipping_phone');
                $table->text('payment_method')->nullable()->after('tracking_number');
                $table->timestampTz('purchase_date')->nullable()->after('payment_method');

                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('vendor_id')->references('ai_uuid')->on('users')->nullOnDelete();
                $table->index('order_number');
                $table->index(['customer_id', 'status']);
            });
        }

        DB::statement('UPDATE orders SET ai_order_id = gen_random_uuid() WHERE ai_order_id IS NULL');
        DB::statement("UPDATE orders SET order_number = 'TH-' || LPAD(id::text, 8, '0') WHERE order_number IS NULL");
        DB::statement('UPDATE orders SET purchase_date = COALESCE(created_at, now()) WHERE purchase_date IS NULL');

        DB::statement(<<<'SQL'
            UPDATE orders
            SET customer_id = customers.id
            FROM customers
            WHERE orders.user_id = customers.user_id
              AND orders.customer_id IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE orders
            SET items = snapshots.items
            FROM (
                SELECT
                    order_items.order_id,
                    jsonb_agg(
                        jsonb_build_object(
                            'product_id', products.id,
                            'name', products.title,
                            'qty', order_items.quantity,
                            'price', order_items.price,
                            'vendor_id', vendors.ai_uuid
                        )
                        ORDER BY order_items.id
                    ) AS items,
                    CASE WHEN COUNT(DISTINCT vendors.ai_uuid) = 1 THEN MIN(vendors.ai_uuid::text)::uuid ELSE NULL END AS vendor_id
                FROM order_items
                INNER JOIN products ON products.id = order_items.product_id
                INNER JOIN users vendors ON vendors.id = products.vendor_id
                GROUP BY order_items.order_id
            ) snapshots
            WHERE orders.id = snapshots.order_id
        SQL);

        DB::statement(<<<'SQL'
            UPDATE orders
            SET vendor_id = snapshots.vendor_id
            FROM (
                SELECT
                    order_items.order_id,
                    CASE WHEN COUNT(DISTINCT vendors.ai_uuid) = 1 THEN MIN(vendors.ai_uuid::text)::uuid ELSE NULL END AS vendor_id
                FROM order_items
                INNER JOIN products ON products.id = order_items.product_id
                INNER JOIN users vendors ON vendors.id = products.vendor_id
                GROUP BY order_items.order_id
            ) snapshots
            WHERE orders.id = snapshots.order_id
              AND orders.vendor_id IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE customers
            SET total_orders = counts.total_orders
            FROM (
                SELECT customer_id, COUNT(*) AS total_orders
                FROM orders
                WHERE customer_id IS NOT NULL
                GROUP BY customer_id
            ) counts
            WHERE customers.id = counts.customer_id
        SQL);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['order_number']);
            $table->dropColumn([
                'ai_order_id',
                'order_number',
                'customer_id',
                'vendor_id',
                'currency',
                'items',
                'shipping_address',
                'shipping_name',
                'shipping_phone',
                'tracking_number',
                'payment_method',
                'purchase_date',
            ]);
        });
    }
};
