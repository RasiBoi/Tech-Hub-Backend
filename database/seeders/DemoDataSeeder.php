<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. VENDORS
        $vendorData = [
            [
                'name'              => 'Lucas Morten',
                'email'             => 'lumorten@techhub.com',
                'store_name'        => 'LumoDesk',
                'avatar_bg'         => 'bg-violet-600 text-white',
                'store_description' => 'Premium minimalist desk accessories crafted for focus-first professionals.',
                'banner_url'        => 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?q=80&w=1368&auto=format&fit=crop',
                'shop_theme'        => 'element',
            ],
            [
                'name'              => 'Anika Patel',
                'email'             => 'anika@techhub.com',
                'store_name'        => 'Anika Workspace Studio',
                'avatar_bg'         => 'bg-pink-600 text-white',
                'store_description' => 'Ergonomic and aesthetic workspace solutions designed for creative professionals.',
                'banner_url'        => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?q=80&w=1368&auto=format&fit=crop',
                'shop_theme'        => 'aurora',
            ],
            [
                'name'              => 'Ryan Cho',
                'email'             => 'rcho@techhub.com',
                'store_name'        => 'ChoTech Gear',
                'avatar_bg'         => 'bg-emerald-600 text-white',
                'store_description' => 'High-tech peripherals and smart desk accessories for the modern workspace.',
                'banner_url'        => 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?q=80&w=1368&auto=format&fit=crop',
                'shop_theme'        => 'cyber',
            ],
            [
                'name'              => 'Priya Fernandez',
                'email'             => 'priya@techhub.com',
                'store_name'        => 'NaturaCraft',
                'avatar_bg'         => 'bg-amber-700 text-white',
                'store_description' => 'Sustainably sourced walnut and bamboo desk organizers with a premium artisan finish.',
                'banner_url'        => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?q=80&w=1368&auto=format&fit=crop',
                'shop_theme'        => 'bloom',
            ],
            [
                'name'              => 'Thomas Webb',
                'email'             => 'twebb@techhub.com',
                'store_name'        => 'PixelPulse Store',
                'avatar_bg'         => 'bg-cyan-600 text-white',
                'store_description' => 'Cyberpunk-inspired gadgets, RGB setups and retro-pixel tech accessories.',
                'banner_url'        => 'https://images.unsplash.com/photo-1607252650355-f7fd0460ccdb?q=80&w=1368&auto=format&fit=crop',
                'shop_theme'        => 'slate',
            ],
        ];

        $createdVendors = [];
        foreach ($vendorData as $v) {
            $vendor = User::create([
                'ai_uuid'           => (string) Str::uuid(),
                'name'              => $v['name'],
                'email'             => $v['email'],
                'password'          => Hash::make('password123'),
                'role'              => 'vendor',
                'store_name'        => $v['store_name'],
                'avatar_bg'         => $v['avatar_bg'],
                'status'            => 'approved',
                'store_description' => $v['store_description'],
                'banner_url'        => $v['banner_url'],
            ]);

            VendorSetting::create([
                'user_id'          => $vendor->id,
                'shop_theme'       => $v['shop_theme'],
                'company_profile'  => $v['store_description'],
                'policy_type'      => 'text',
                'policy_text'      => 'All purchases are subject to a 14-day return policy. Items must be in original condition with packaging.',
            ]);

            $createdVendors[] = $vendor;
        }

        // 2. REDISTRIBUTE PRODUCTS equally across the 5 new vendors (round-robin)
        $products = Product::orderBy('id')->get();
        foreach ($products as $index => $product) {
            $product->update(['vendor_id' => $createdVendors[$index % 5]->id]);
        }

        // 3. CUSTOMERS
        $customerData = [
            ['name' => 'Emma Thompson',  'email' => 'emma.t@example.com',   'avatar_bg' => 'bg-sky-500 text-white'],
            ['name' => 'Marcus Williams', 'email' => 'marcus.w@example.com', 'avatar_bg' => 'bg-teal-600 text-white'],
            ['name' => 'Sofia Reyes',    'email' => 'sofia.r@example.com',   'avatar_bg' => 'bg-purple-600 text-white'],
            ['name' => 'James Kim',      'email' => 'james.k@example.com',   'avatar_bg' => 'bg-orange-500 text-white'],
            ['name' => 'Aisha Ndiaye',   'email' => 'aisha.n@example.com',   'avatar_bg' => 'bg-rose-500 text-white'],
        ];

        $custUsers    = [];
        $custProfiles = [];

        foreach ($customerData as $c) {
            $uuid = (string) Str::uuid();
            $user = User::create([
                'ai_uuid'   => $uuid,
                'name'      => $c['name'],
                'email'     => $c['email'],
                'password'  => Hash::make('password123'),
                'role'      => 'customer',
                'avatar_bg' => $c['avatar_bg'],
            ]);

            $profile = Customer::create([
                'id'            => $uuid,
                'user_id'       => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'tier'          => 'standard',
                'total_orders'  => 0,
                'dispute_count' => 0,
            ]);

            $custUsers[]    = $user;
            $custProfiles[] = $profile;
        }

        // 4. ORDERS (5 orders — one per customer)
        $allProducts = Product::orderBy('id')->get();

        $orderConfigs = [
            [
                'ci'      => 0,
                'items'   => [[0, 1], [1, 1]],
                'name'    => 'Emma Thompson',
                'phone'   => '+94 771 234 567',
                'address' => '42 Lotus Road, Colombo 03, Sri Lanka',
                'payment' => 'Credit Card',
                'status'  => 'delivered',
                'days'    => 14,
            ],
            [
                'ci'      => 1,
                'items'   => [[12, 1]],
                'name'    => 'Marcus Williams',
                'phone'   => '+94 770 345 678',
                'address' => '7 Station Road, Kandy, Sri Lanka',
                'payment' => 'Bank Transfer',
                'status'  => 'dispatched',
                'days'    => 5,
            ],
            [
                'ci'      => 2,
                'items'   => [[8, 1]],
                'name'    => 'Sofia Reyes',
                'phone'   => '+94 777 456 789',
                'address' => '15 Galle Face Terrace, Colombo 01, Sri Lanka',
                'payment' => 'Cash on Delivery',
                'status'  => 'processing',
                'days'    => 2,
            ],
            [
                'ci'      => 3,
                'items'   => [[17, 1], [6, 1]],
                'name'    => 'James Kim',
                'phone'   => '+94 776 567 890',
                'address' => '88 Bandarawela Road, Nuwara Eliya, Sri Lanka',
                'payment' => 'Credit Card',
                'status'  => 'delivered',
                'days'    => 21,
            ],
            [
                'ci'      => 4,
                'items'   => [[13, 1]],
                'name'    => 'Aisha Ndiaye',
                'phone'   => '+94 775 678 901',
                'address' => '3 Marine Drive, Galle, Sri Lanka',
                'payment' => 'Cash on Delivery',
                'status'  => 'processing',
                'days'    => 1,
            ],
        ];

        foreach ($orderConfigs as $cfg) {
            $cUser    = $custUsers[$cfg['ci']];
            $cProfile = $custProfiles[$cfg['ci']];

            $total     = 0;
            $snapshots = [];
            $vUuids    = [];
            $oItems    = [];

            foreach ($cfg['items'] as [$pidx, $qty]) {
                $product = $allProducts->get($pidx) ?? $allProducts->first();
                $price   = $product->price;
                $total  += $price * $qty;

                $vendor  = User::find($product->vendor_id);
                $vUuid   = $vendor?->ai_uuid;
                if ($vUuid) { $vUuids[] = $vUuid; }

                $snapshots[] = [
                    'product_id' => $product->id,
                    'name'       => $product->title,
                    'qty'        => $qty,
                    'price'      => (float) $price,
                    'vendor_id'  => $vUuid,
                ];

                $oItems[] = [
                    'product_id' => $product->id,
                    'price'      => $price,
                    'quantity'   => $qty,
                    'status'     => in_array($cfg['status'], ['delivered', 'dispatched']) ? 'dispatched' : 'pending',
                ];
            }

            $uniqueVUuids = array_values(array_unique(array_filter($vUuids)));

            $order = Order::create([
                'ai_order_id'      => (string) Str::uuid(),
                'user_id'          => $cUser->id,
                'customer_id'      => $cProfile->id,
                'vendor_id'        => count($uniqueVUuids) === 1 ? $uniqueVUuids[0] : null,
                'total_amount'     => $total,
                'currency'         => 'LKR',
                'items'            => $snapshots,
                'shipping_name'    => $cfg['name'],
                'shipping_phone'   => $cfg['phone'],
                'shipping_address' => $cfg['address'],
                'payment_method'   => $cfg['payment'],
                'purchase_date'    => now()->subDays($cfg['days']),
                'status'           => $cfg['status'],
            ]);

            $order->update([
                'order_number' => 'TH-' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT),
            ]);

            foreach ($oItems as $item) {
                $order->orderItems()->create($item);
            }

            $cProfile->update([
                'total_orders' => Order::where('customer_id', $cProfile->id)->count(),
            ]);
        }

        $this->command->info('DemoDataSeeder: 5 vendors, products redistributed, 5 customers, 5 orders seeded.');
    }
}
