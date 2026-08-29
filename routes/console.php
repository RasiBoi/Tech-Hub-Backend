<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('catalog:sync-media {--limit= : Limit imported product folders}', function () {
    $mediaRoot = base_path('../Tech-Hub/Media/product_images');
    if (!is_dir($mediaRoot)) {
        $this->error("Media directory not found: {$mediaRoot}");
        return 1;
    }

    $vendorNames = ['LumoDesk', 'Anika Workspace Studio', 'ChoTech Gear', 'NaturaCraft', 'PixelPulse Store'];
    $vendors = User::whereIn('store_name', $vendorNames)->where('role', 'vendor')->orderBy('id')->get()->values();
    if ($vendors->count() !== count($vendorNames)) {
        $this->error('Expected seeded vendors were not found.');
        return 1;
    }

    $categorySeeds = [
        'Stands & Holders' => 'baseus-foldable-desktop-phone-stand-portable-and-adjustable-universal-holder-for-phones-tablets-and-ipads',
        'Desk Organizers' => 'premium-walnut-desk-organizer-the-c-level-collection',
        'Desk Mats' => 'simplist-desk-mat-pro-plus',
        'Lighting' => 'baseus-smart-eye-foldable-desk-lamp',
        'Clocks & Timers' => 'baseus-heyo-rotation-countdown-timer',
        'Charging Stations' => 'baseus-magpro-3-in-1-wireless-charging-station',
        'Monitor Raisers' => 'ugreen-monitor-raiser-stand',
        'Standing Desks' => 'flexispot-e7-height-adjustable-ergonomic-standing-desk',
        'Ergonomic Chairs' => 'flexispot-c7-premium-ergonomic-chair',
        'Audio & Speakers' => 'edifier-mr4-studio-monitors',
        'Cable Managers' => 'fasola-cable-management-box-for-power-strips-and-electrical-cords-organize-and-conceal-wires',
        'Cleaning & Comfort' => 'baseus-ultraclean-series-multifunctional-cleaning-kit',
        'Fans & Humidifiers' => 'airbuddy-pro-rechargeable-portable-fan',
        'Decor & Plants' => 'ama-zen-plant-propagation-tube-stylish-bulb-design-for-growing-roots',
        'Stress Reliever' => 'kinetic-roller-coaster-perpetual-motion-toy',
    ];

    $firstImageFor = function (string $folder): string {
        $path = base_path("../Tech-Hub/Media/product_images/{$folder}");
        $file = collect(File::files($path))
            ->first(fn ($candidate) => preg_match('/^image-1\.(png|jpe?g|webp)$/i', $candidate->getFilename()));

        return "../../Media/product_images/{$folder}/" . ($file?->getFilename() ?? 'image-1.png');
    };

    $categories = collect($categorySeeds)->mapWithKeys(function ($folder, $name) use ($firstImageFor) {
        return [$name => Category::updateOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'image' => $firstImageFor($folder),
            ]
        )];
    });

    $classify = function (string $slug): array {
        $rules = [
            ['Charging Stations', 'USB Hubs & Docks', ['hub', 'powerexpand', 'usb-c', 'usb']],
            ['Charging Stations', 'Wireless Chargers', ['charging', 'charger', 'magsafe', 'qi2']],
            ['Cable Managers', 'Cable Boxes', ['cable-management-box', 'power-strips']],
            ['Cable Managers', 'Cable Clips & Ties', ['cable', 'cord', 'wire', 'velcro']],
            ['Lighting', 'Monitor Light Bars', ['monitor-light', 'light-bar', 'i-wok']],
            ['Lighting', 'Desk Lamps', ['lamp', 'reading-mini-clip']],
            ['Clocks & Timers', 'Digital Timers', ['timer', 'countdown']],
            ['Clocks & Timers', 'Pixel Clocks', ['clock', 'timebox', 'times-gate', 'nixie']],
            ['Monitor Raisers', 'Monitor Arms', ['monitor-arm', 'dual-monitor-arm']],
            ['Monitor Raisers', 'Monitor Stands', ['monitor-stand', 'monitor-riser', 'dual-monitor-stand']],
            ['Standing Desks', 'Electric Standing Desks', ['standing-desk', 'height-adjustable']],
            ['Ergonomic Chairs', 'Task Chairs', ['chair', 'ergonomic-chair', 'swivel']],
            ['Stands & Holders', 'Laptop Stands', ['laptop-stand', 'notebook-bracket']],
            ['Stands & Holders', 'Phone & Tablet Stands', ['phone-stand', 'tablet', 'ipad', 'lazy-phone']],
            ['Stands & Holders', 'Headphone Stands', ['headphone', 'headset']],
            ['Desk Organizers', 'Pegboards & Racks', ['pegboard', 'rack', 'book-and-accessories']],
            ['Desk Organizers', 'Pen & Drawer Organizers', ['pen-holder', 'drawer', 'organizer']],
            ['Desk Mats', 'Desk Mats', ['deskmat', 'desk-mat', 'mouse-pad']],
            ['Audio & Speakers', 'Speakers', ['speaker', 'studio-monitors', 'edifier']],
            ['Fans & Humidifiers', 'Fans', ['fan']],
            ['Fans & Humidifiers', 'Humidifiers & Diffusers', ['humidifier', 'diffuser', 'aroma', 'essential-oil']],
            ['Cleaning & Comfort', 'Cleaning Kits', ['cleaning', 'microfiber', 'duster']],
            ['Cleaning & Comfort', 'Wrist & Foot Rests', ['wrist-rest', 'foot-rest']],
            ['Decor & Plants', 'Plants & Decor', ['plant', 'propagation', 'candle']],
            ['Stress Reliever', 'Fidget & Desk Toys', ['fidget', 'decompression', 'springy', 'kinetic']],
        ];

        foreach ($rules as [$category, $subcategory, $needles]) {
            foreach ($needles as $needle) {
                if (str_contains($slug, $needle)) {
                    return [$category, $subcategory];
                }
            }
        }

        return ['Desk Organizers', 'Workspace Accessories'];
    };

    $brandFromSlug = function (string $slug): string {
        $first = Str::of($slug)->before('-')->replace('_', ' ')->title()->toString();
        return match (strtolower($first)) {
            'mi' => 'Xiaomi',
            'ugreen' => 'Ugreen',
            'baseus' => 'Baseus',
            'flexispot' => 'FlexiSpot',
            'divoom' => 'Divoom',
            'fasola' => 'Fasola',
            'anker' => 'Anker',
            default => $first,
        };
    };

    $vibeFromSlug = function (string $slug): ?string {
        if (str_contains($slug, 'walnut') || str_contains($slug, 'wood')) return 'walnut';
        if (str_contains($slug, 'black') || str_contains($slug, 'carbon')) return 'black';
        if (str_contains($slug, 'pixel') || str_contains($slug, 'rgb') || str_contains($slug, 'nixie')) return 'cyberpunk';
        return 'minimalist';
    };

    $folders = collect(File::directories($mediaRoot))->sort()->values();
    if ($this->option('limit')) {
        $folders = $folders->take((int) $this->option('limit'));
    }

    $imported = 0;
    foreach ($folders as $index => $folderPath) {
        $slug = basename($folderPath);
        $images = collect(File::files($folderPath))
            ->filter(fn ($file) => preg_match('/\.(png|jpe?g|webp)$/i', $file->getFilename()))
            ->sortBy(fn ($file) => str_pad((string) (preg_match('/image-(\d+)/i', $file->getFilename(), $m) ? $m[1] : 999), 4, '0', STR_PAD_LEFT) . $file->getFilename())
            ->map(fn ($file) => "../../Media/product_images/{$slug}/{$file->getFilename()}")
            ->values()
            ->all();

        if (count($images) === 0) {
            continue;
        }

        [$categoryName, $subcategory] = $classify($slug);
        $category = $categories[$categoryName] ?? $categories['Desk Organizers'];
        $title = Str::of($slug)->replace('-', ' ')->title()->toString();
        $vendor = $vendors[$index % $vendors->count()];
        $price = max(2500, min(225000, (strlen($slug) * 725) + (($index % 9) * 1250)));
        $oldPrice = round($price * 1.18, -2);

        Product::updateOrCreate(
            ['title' => $title],
            [
                'description' => "{$title} curated for premium desk setups, productivity-focused workstations, and modern home office environments.",
                'price' => $price,
                'old_price' => $oldPrice,
                'brand' => $brandFromSlug($slug),
                'subcategory' => $subcategory,
                'image' => $images[0],
                'images' => $images,
                'spec' => Str::headline($subcategory),
                'stock' => 12 + ($index % 24),
                'rating' => 4.4 + (($index % 6) / 10),
                'reviews_count' => 18 + (($index * 7) % 280),
                'category_id' => $category->id,
                'vendor_id' => $vendor->id,
                'vibe' => $vibeFromSlug($slug),
            ]
        );

        $imported++;
    }

    Cache::flush();
    $this->info("Synced {$imported} media-backed products across {$vendors->count()} vendors.");
    return 0;
})->purpose('Sync Tech-Hub media folders into the catalog database');

Artisan::command('ai:backfill-policies', function () {
    $webhook = config('services.ai.policy_sync_webhook_url');
    if (!$webhook) {
        $this->error('AI_POLICY_SYNC_WEBHOOK_URL is not set.');
        return 1;
    }

    $vendors = \App\Models\VendorPolicy::where('approved_by_admin', true)->get();
    foreach ($vendors as $policy) {
        \App\Jobs\SyncPolicyToAiJob::dispatchSync(
            'UPDATE',
            'vendor_policies',
            [
                'id' => $policy->id,
                'vendor_id' => $policy->vendor_id,
                'policy_name' => $policy->policy_name,
                'policy_type' => $policy->policy_type,
                'max_return_days' => $policy->max_return_days,
                'refund_type' => $policy->refund_type,
                'restocking_fee_percent' => $policy->restocking_fee_percent,
                'conditions' => $policy->conditions,
                'document_format' => $policy->document_format,
                'policy_body' => $policy->policy_body,
                'document_url' => $policy->document_url,
                'approved_by_admin' => true,
            ],
        );
        $this->line("Queued vendor policy {$policy->id}");
    }

    $platforms = \App\Models\PlatformPolicy::all();
    foreach ($platforms as $policy) {
        \App\Jobs\SyncPolicyToAiJob::dispatchSync(
            'UPDATE',
            'platform_policies',
            [
                'id' => $policy->id,
                'policy_key' => $policy->policy_key,
                'policy_name' => $policy->policy_name,
                'max_value' => $policy->max_value,
                'min_value' => $policy->min_value,
                'is_mandatory' => (bool) $policy->is_mandatory,
            ],
        );
        $this->line("Queued platform policy {$policy->id}");
    }

    $this->info("Backfill complete: {$vendors->count()} vendor + {$platforms->count()} platform policies.");
    return 0;
})->purpose('Re-sync approved AI policies to Neo4j + Qdrant via webhook');
