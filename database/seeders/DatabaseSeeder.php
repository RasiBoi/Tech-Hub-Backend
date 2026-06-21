<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users (Roles: admin, vendor, customer)
        $admin = User::create([
            'name' => 'Sarah (Platform Admin)',
            'email' => 'admin@techhub.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'avatar_bg' => 'bg-rose-500 text-white',
        ]);

        $vendorApple = User::create([
            'name' => 'Apple Store Inc.',
            'email' => 'vendor@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'store_name' => 'Apple Official Store',
            'avatar_bg' => 'bg-slate-800 text-white',
        ]);

        $vendorSamsung = User::create([
            'name' => 'Samsung Official',
            'email' => 'samsung@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'store_name' => 'Samsung Official Display',
            'avatar_bg' => 'bg-blue-800 text-white',
        ]);

        $vendorDell = User::create([
            'name' => 'Dell Partner',
            'email' => 'dell@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'store_name' => 'Dell Partner Center',
            'avatar_bg' => 'bg-cyan-700 text-white',
        ]);

        $vendorSony = User::create([
            'name' => 'Sony Store',
            'email' => 'sony@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'store_name' => 'Sony Audio Labs',
            'avatar_bg' => 'bg-zinc-800 text-white',
        ]);

        $vendorXiaomi = User::create([
            'name' => 'Xiaomi Store',
            'email' => 'xiaomi@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'store_name' => 'Xiaomi IoT Home',
            'avatar_bg' => 'bg-orange-600 text-white',
        ]);

        $vendorBeats = User::create([
            'name' => 'Beats Official',
            'email' => 'beats@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
            'store_name' => 'Beats Acoustics',
            'avatar_bg' => 'bg-red-600 text-white',
        ]);

        $customer = User::create([
            'name' => 'Alex Johnson',
            'email' => 'customer@techhub.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'avatar_bg' => 'bg-blue-600 text-white',
        ]);

        // 2. Seed Categories
        $categoriesData = [
            ['name' => 'Stands & Holders', 'image' => '../../Media/product_images/baseus-foldable-desktop-phone-stand-portable-and-adjustable-universal-holder-for-phones-tablets-and-ipads/image-1.jpg'],
            ['name' => 'Desk Organizers', 'image' => '../../Media/product_images/premium-walnut-desk-organizer-the-c-level-collection/image-1.png'],
            ['name' => 'Desk Mats', 'image' => '../../Media/product_images/simplist-desk-mat-pro-plus/image-1.png'],
            ['name' => 'Lighting', 'image' => '../../Media/product_images/baseus-smart-eye-foldable-desk-lamp/image-1.webp'],
            ['name' => 'Clocks & Timers', 'image' => '../../Media/product_images/baseus-heyo-rotation-countdown-timer/image-1.jpg'],
            ['name' => 'Charging Stations', 'image' => '../../Media/product_images/baseus-magpro-3-in-1-wireless-charging-station/image-1.png'],
            ['name' => 'Monitor Raisers', 'image' => '../../Media/product_images/ugreen-monitor-raiser-stand/image-1.png'],
            ['name' => 'Standing Desks', 'image' => '../../Media/product_images/flexispot-e7-height-adjustable-ergonomic-standing-desk/image-1.png'],
            ['name' => 'Ergonomic Chairs', 'image' => '../../Media/product_images/flexispot-c7-premium-ergonomic-chair/image-1.jpeg'],
            ['name' => 'Stress Reliever', 'image' => '../../Media/product_images/kinetic-roller-coaster-perpetual-motion-toy/image-1.webp'],
            ['name' => 'Cable Managers', 'image' => '../../Media/product_images/fasola-cable-management-box-for-power-strips-and-electrical-cords-organize-and-conceal-wires/image-1.webp'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['name']] = Category::create([
                'name' => $c['name'],
                'slug' => Str::slug($c['name']),
                'image' => $c['image'],
            ]);
        }

        // 3. Seed Products
        $productsData = [
            // Walnut Vibe
            [
                'title' => 'Ugreen Walnut Monitor Raiser Stand',
                'description' => 'A beautiful monitor raiser crafted from solid walnut wood with integrated drawer storage.',
                'price' => 18900.00,
                'image' => '../../Media/product_images/ugreen-monitor-raiser-stand/image-1.png',
                'spec' => 'Walnut Wood Drawer',
                'vibe' => 'walnut',
                'category' => 'Monitor Raisers',
                'vendor_id' => $vendorSamsung->id,
                'rating' => 4.90,
                'reviews_count' => 142
            ],
            [
                'title' => 'Premium Walnut Desk Organizer',
                'description' => 'Solid walnut desk organizer from the C-Level Collection to keep your accessories clean and tidy.',
                'price' => 12900.00,
                'image' => '../../Media/product_images/premium-walnut-desk-organizer-the-c-level-collection/image-1.png',
                'spec' => 'C-Level solid walnut wood grains',
                'vibe' => 'walnut',
                'category' => 'Desk Organizers',
                'vendor_id' => $vendorApple->id,
                'rating' => 4.80,
                'reviews_count' => 96
            ],
            [
                'title' => 'Walnut Luxe Headphone Stand',
                'description' => 'Keep your headphones elegantly suspended on a solid walnut and anodized aluminum mount.',
                'price' => 9500.00,
                'image' => '../../Media/product_images/walnut-luxe-headphone-stand/image-1.jpeg',
                'spec' => 'Solid Walnut Base',
                'vibe' => 'walnut',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorBeats->id,
                'rating' => 4.70,
                'reviews_count' => 64
            ],
            [
                'title' => 'FlexiSpot E7 Ergonomic standing desk',
                'description' => 'Height-adjustable standing desk with solid wood walnut top and robust dual-motor steel frame.',
                'price' => 185000.00,
                'image' => '../../Media/product_images/flexispot-e7-height-adjustable-ergonomic-standing-desk/image-1.png',
                'spec' => 'Dual motor, stable premium steel frame',
                'vibe' => 'walnut',
                'category' => 'Standing Desks',
                'vendor_id' => $vendorSamsung->id,
                'rating' => 4.90,
                'reviews_count' => 204
            ],

            // Minimalist Vibe
            [
                'title' => 'Baseus Smart Eye Foldable Desk Lamp',
                'description' => 'Smart eye protection desk lamp with automatic brightness adjustments and space-saving foldability.',
                'price' => 12800.00,
                'image' => '../../Media/product_images/baseus-smart-eye-foldable-desk-lamp/image-1.webp',
                'spec' => 'Auto-Dimming eye care light bar',
                'vibe' => 'minimalist',
                'category' => 'Lighting',
                'vendor_id' => $vendorXiaomi->id,
                'rating' => 4.80,
                'reviews_count' => 88
            ],
            [
                'title' => 'Simplist Desk Mat Pro Plus',
                'description' => 'Premium desk mat with clean textures, adding warm visuals and perfect mouse precision.',
                'price' => 6400.00,
                'image' => '../../Media/product_images/simplist-desk-mat-pro-plus/image-1.png',
                'spec' => 'Non-slip water resistant soft felt',
                'vibe' => 'minimalist',
                'category' => 'Desk Mats',
                'vendor_id' => $vendorApple->id,
                'rating' => 4.70,
                'reviews_count' => 110
            ],
            [
                'title' => 'Baseus MagPro 3-in-1 Charging Station',
                'description' => 'Organize your Apple ecosystem chargers into a single, clean charging stand.',
                'price' => 21900.00,
                'image' => '../../Media/product_images/baseus-magpro-3-in-1-wireless-charging-station/image-1.png',
                'spec' => 'Qi2 MagSafe Mount 15W',
                'vibe' => 'minimalist',
                'category' => 'Charging Stations',
                'vendor_id' => $vendorApple->id,
                'rating' => 4.80,
                'reviews_count' => 156
            ],
            [
                'title' => 'Fasola Cable Management Box (White)',
                'description' => 'Hide power strips and chaotic cords in a stylish matte white design box.',
                'price' => 4500.00,
                'image' => '../../Media/product_images/fasola-cable-management-box-for-power-strips-and-electrical-cords-organize-and-conceal-wires/image-1.webp',
                'spec' => 'Conceal power strip & messy lines',
                'vibe' => 'minimalist',
                'category' => 'Cable Managers',
                'vendor_id' => $vendorDell->id,
                'rating' => 4.60,
                'reviews_count' => 74
            ],
            [
                'title' => 'FlexiSpot C7 Premium Ergonomic Chair',
                'description' => 'Premium posture support chair with self-adaptive lumbar tracking and breathable mesh.',
                'price' => 98500.00,
                'image' => '../../Media/product_images/flexispot-c7-premium-ergonomic-chair/image-1.jpeg',
                'spec' => 'Adaptive lumbar tracking support',
                'vibe' => 'minimalist',
                'category' => 'Ergonomic Chairs',
                'vendor_id' => $vendorDell->id,
                'rating' => 4.90,
                'reviews_count' => 134
            ],

            // Stealth Black Vibe
            [
                'title' => 'Ugreen Aluminum Monitor Raiser (Black)',
                'description' => 'Anodized aluminum alloy monitor stand in stealth matte black, optimizing workspace geometry.',
                'price' => 18900.00,
                'image' => '../../Media/product_images/ugreen-monitor-raiser-stand/image-1.png', // Fallback to main image
                'spec' => 'Heavy duty black anodized alloy',
                'vibe' => 'black',
                'category' => 'Monitor Raisers',
                'vendor_id' => $vendorSamsung->id,
                'rating' => 4.80,
                'reviews_count' => 95
            ],
            [
                'title' => 'Baseus Smart Eye Desk Lamp (Black)',
                'description' => 'Smart targeted desk light in matte black, perfect for relaxing night sessions.',
                'price' => 12800.00,
                'image' => '../../Media/product_images/baseus-smart-eye-foldable-desk-lamp/image-2.webp',
                'spec' => 'Stealth matte black task lamp',
                'vibe' => 'black',
                'category' => 'Lighting',
                'vendor_id' => $vendorXiaomi->id,
                'rating' => 4.80,
                'reviews_count' => 102
            ],
            [
                'title' => 'Fasola Cable Management Box (Black)',
                'description' => 'Hide extension sockets and cable nests inside a solid matte black storage case.',
                'price' => 4500.00,
                'image' => '../../Media/product_images/fasola-cable-management-box-for-power-strips-and-electrical-cords-organize-and-conceal-wires/image-1.webp',
                'spec' => 'Matte black fire-retardant casing',
                'vibe' => 'black',
                'category' => 'Cable Managers',
                'vendor_id' => $vendorDell->id,
                'rating' => 4.60,
                'reviews_count' => 42
            ],
            [
                'title' => 'Ugreen Qi2 2-in-1 Robot Charging Dock',
                'description' => 'A playful mechanical robot charging station with a Qi2 magnetic wireless charger.',
                'price' => 18900.00,
                'image' => '../../Media/product_images/ugreen-qi2-2-in-1-wireless-robot-charging-station/image-1.png',
                'spec' => 'Robot styled Qi2 15W charging station',
                'vibe' => 'black',
                'category' => 'Charging Stations',
                'vendor_id' => $vendorApple->id,
                'rating' => 4.90,
                'reviews_count' => 120
            ],

            // Cyberpunk Vibe
            [
                'title' => 'Divoom Times Gate Digital Clock',
                'description' => 'Futuristic smart clock with five programmable LCD screens showing real-time stats and retro pixel arts.',
                'price' => 42900.00,
                'image' => '../../Media/product_images/divoom-times-gate-pixel-art-informative-display/image-1.jpeg',
                'spec' => 'Five IPS LCD screens pixel arts',
                'vibe' => 'cyberpunk',
                'category' => 'Clocks & Timers',
                'vendor_id' => $vendorSony->id,
                'rating' => 4.90,
                'reviews_count' => 254
            ],
            [
                'title' => 'Divoom Ditoo Retro Pixel Speaker',
                'description' => 'Retro arcade console styled smart Bluetooth speaker with built-in programmable pixel display panel.',
                'price' => 31500.00,
                'image' => '../../Media/product_images/divoom-ditoo-pro-retro-pixel-art-bluetooth-speaker/image-1.jpeg',
                'spec' => '10W DSP Audio arcade controls',
                'vibe' => 'cyberpunk',
                'category' => 'Stress Reliever',
                'vendor_id' => $vendorSony->id,
                'rating' => 4.80,
                'reviews_count' => 165
            ],
            [
                'title' => 'Kinetic Roller Coaster Perpetual Motion',
                'description' => 'Add perpetual physics energy to your neon styled cyberpunk workspace setup.',
                'price' => 14500.00,
                'image' => '../../Media/product_images/kinetic-roller-coaster-perpetual-motion-toy/image-1.webp',
                'spec' => 'Perpetual motion electromagnetic device',
                'vibe' => 'cyberpunk',
                'category' => 'Stress Reliever',
                'vendor_id' => $vendorSony->id,
                'rating' => 4.60,
                'reviews_count' => 84
            ],
            [
                'title' => 'Baseus rotation Countdown Timer',
                'description' => 'Heyo rotating work and rest control dial with large LED digital display.',
                'price' => 4900.00,
                'image' => '../../Media/product_images/baseus-heyo-rotation-countdown-timer/image-1.jpg',
                'spec' => 'Heyo Rotary Control Dial',
                'vibe' => 'cyberpunk',
                'category' => 'Clocks & Timers',
                'vendor_id' => $vendorXiaomi->id,
                'rating' => 4.70,
                'reviews_count' => 112
            ],

            // Other catalog products to complete vendors profiles
            [
                'title' => 'Tablet iPad Dock Stand',
                'description' => 'Aluminum desktop dock mount with a 360° rotating base for tablets and iPads.',
                'price' => 14500.00,
                'image' => '../../Media/product_images/upergo-tablet-ipad-dock-stand-aluminium-silver/image-1.jpg',
                'spec' => '360° Riser Base',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorApple->id,
                'rating' => 4.75,
                'reviews_count' => 54
            ],
            [
                'title' => 'Kaloc Premium Monitor Arm',
                'description' => 'Kaloc heavy-duty single monitor gas-spring mount for full desk articulation.',
                'price' => 16500.00,
                'image' => '../../Media/product_images/kaloc-xs100g-premium-aluminum-monitor-arm/image-1.png',
                'spec' => 'Heavy Duty Gas Spring',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorSamsung->id,
                'rating' => 4.80,
                'reviews_count' => 112
            ],
            [
                'title' => 'Premium Dual Monitor Stand',
                'description' => 'Upergo walnut double monitor riser stand to elevate dual monitors.',
                'price' => 35000.00,
                'image' => '../../Media/product_images/upergo-premium-walnut-dual-monitor-riser-stand-vd-42t/image-1.png',
                'spec' => 'Desk Space Optimizer',
                'category' => 'Monitor Raisers',
                'vendor_id' => $vendorSamsung->id,
                'rating' => 4.85,
                'reviews_count' => 74
            ],
            [
                'title' => 'N3 Laptop Stand',
                'description' => 'Compact folding aluminum laptop stand with multi-angle ventilation adjustments.',
                'price' => 9500.00,
                'image' => '../../Media/product_images/n3-laptop-stand/image-1.jpg',
                'spec' => 'Foldable Riser Bracket',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorDell->id,
                'rating' => 4.65,
                'reviews_count' => 208
            ],
            [
                'title' => 'Ugreen Vertical Laptop Stand',
                'description' => 'Space saving gravity locking vertical holder for laptops and MacBooks.',
                'price' => 7500.00,
                'image' => '../../Media/product_images/ugreen-vertical-laptop-stand-adjustable-laptop-holder/image-1.jpg',
                'spec' => 'Gravity Lock Spacer',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorDell->id,
                'rating' => 4.70,
                'reviews_count' => 143
            ],
            [
                'title' => 'Portable Adjustable Laptop Stand',
                'description' => 'Aluminum adjustable laptop stand to raise screen height for typing comfort.',
                'price' => 11900.00,
                'image' => '../../Media/product_images/upergo-portable-laptop-stand/image-1.jpeg',
                'spec' => 'Ergonomic Aluminium Base',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorDell->id,
                'rating' => 4.75,
                'reviews_count' => 64
            ],
            [
                'title' => 'Edifier Studio Monitors',
                'description' => 'High quality Edifier MR4 active near-field studio monitors for crisp acoustic playback.',
                'price' => 55000.00,
                'image' => '../../Media/product_images/edifier-mr4-studio-monitors/image-1.png',
                'spec' => 'Studio Acoustic Audio',
                'category' => 'Stress Reliever',
                'vendor_id' => $vendorSony->id,
                'rating' => 4.85,
                'reviews_count' => 125
            ],
            [
                'title' => 'Divoom Tiivoo Speaker',
                'description' => 'Classic television cabinet styled smart speaker with pixel art screen layout.',
                'price' => 29900.00,
                'image' => '../../Media/product_images/divoom-tiivoo-2-photo-album-bluetooth-speaker/image-1.jpeg',
                'spec' => 'Retro Cabinet Design',
                'category' => 'Stress Reliever',
                'vendor_id' => $vendorSony->id,
                'rating' => 4.70,
                'reviews_count' => 96
            ],
            [
                'title' => 'Mi Monitor Light Bar',
                'description' => 'Xiaomi Mi computer monitor asymmetric path light bar with wireless remote control.',
                'price' => 15900.00,
                'image' => '../../Media/product_images/mi-computer-monitor-light-bar-black/image-1.jpg',
                'spec' => 'Asymmetric Eye Protection',
                'category' => 'Lighting',
                'vendor_id' => $vendorXiaomi->id,
                'rating' => 4.80,
                'reviews_count' => 234
            ],
            [
                'title' => 'Mi Smart Desk Lamp',
                'description' => 'Wi-Fi enabled Mi smart desk lamp 1S with adjustable color temperature and brightness.',
                'price' => 19900.00,
                'image' => '../../Media/product_images/mi-1s-smart-led-desk-lamp/image-1.png',
                'spec' => 'Wi-Fi Intelligent Control',
                'category' => 'Lighting',
                'vendor_id' => $vendorXiaomi->id,
                'rating' => 4.75,
                'reviews_count' => 164
            ],
            [
                'title' => 'Apex Solid Walnut Stand',
                'description' => 'Solid walnut wood double headphone stand hanger with steel framing.',
                'price' => 12900.00,
                'image' => '../../Media/product_images/the-apex-stand-solid-walnut-wood-headphone-holder-stand-for-minimalist-desk-setups/image-1.png',
                'spec' => 'Premium Wood Hanger',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorBeats->id,
                'rating' => 4.85,
                'reviews_count' => 82
            ],
            [
                'title' => 'Solo Headset Stand',
                'description' => 'Minimalist detachable aluminum alloy headset holder for general over-ear headphones.',
                'price' => 8500.00,
                'image' => '../../Media/product_images/simplist-solo-headset-holder-detachable-aluminum-alloy-portable-headphone-stand/image-1.jpg',
                'spec' => 'Universal Metal Bracket',
                'category' => 'Stands & Holders',
                'vendor_id' => $vendorBeats->id,
                'rating' => 4.60,
                'reviews_count' => 45
            ],
        ];

        foreach ($productsData as $p) {
            Product::create([
                'title' => $p['title'],
                'description' => $p['description'],
                'price' => $p['price'],
                'image' => $p['image'],
                'spec' => $p['spec'],
                'stock' => 15,
                'rating' => $p['rating'],
                'reviews_count' => $p['reviews_count'],
                'category_id' => $categories[$p['category']]->id,
                'vendor_id' => $p['vendor_id'],
                'vibe' => $p['vibe'] ?? null,
            ]);
        }
    }
}
