<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => 'Tech-Hub AI Recommendation Engine'
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $message = strtolower($request->message);
        
        // Find matching products in database
        $vibe = null;
        if (str_contains($message, 'walnut') || str_contains($message, 'wood') || str_contains($message, 'organic')) {
            $vibe = 'walnut';
        } elseif (str_contains($message, 'minimal') || str_contains($message, 'cream') || str_contains($message, 'clean')) {
            $vibe = 'minimalist';
        } elseif (str_contains($message, 'black') || str_contains($message, 'stealth') || str_contains($message, 'dark')) {
            $vibe = 'black';
        } elseif (str_contains($message, 'cyber') || str_contains($message, 'rgb') || str_contains($message, 'neon') || str_contains($message, 'clock') || str_contains($message, 'speaker')) {
            $vibe = 'cyberpunk';
        }

        $query = Product::with(['category', 'vendor']);
        if ($vibe) {
            $query->where('vibe', $vibe);
        } else {
            // General keywords search
            $keywords = ['stand', 'mat', 'organizer', 'lamp', 'timer', 'charger', 'desk', 'chair', 'speaker'];
            $foundKeyword = false;
            foreach ($keywords as $kw) {
                if (str_contains($message, $kw)) {
                    $query->where('title', 'like', "%{$kw}%");
                    $foundKeyword = true;
                    break;
                }
            }
            if (!$foundKeyword) {
                // Return random recommendations
                $query->inRandomOrder();
            }
        }

        $recommendedProducts = $query->limit(3)->get()->map(function($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'image' => $product->image,
                'spec' => $product->spec,
                'rating' => $product->rating,
                'vibe' => $product->vibe
            ];
        });

        // Determine AI response text based on message
        if ($vibe === 'walnut') {
            $text = "I recommend checking out our **Walnut & Organic** theme! It features rich walnut wood organizers and premium acoustic stands that add natural warmth to your desk. Here are some top-rated walnut items from our catalog:";
        } elseif ($vibe === 'minimalist') {
            $text = "If you like clean spaces, the **Cream Minimalist** theme is perfect! It uses warm cream desk mats and charging docks to reduce visual clutter. Take a look at these minimal accents:";
        } elseif ($vibe === 'black') {
            $text = "A **Stealth Matte Black** look is excellent for high focus. Incorporating black anodized metals, matte managers, and target task lights will look sleek. Try these matte black essentials:";
        } elseif ($vibe === 'cyberpunk') {
            $text = "For a high-energy setup, try **Cyberpunk RGB**! Utilizing customizable pixel art displays, ambient lights, and retro speakers really highlights the desk style. Check out these cyberpunk items:";
        } else {
            $text = "I'd love to help you build your perfect workspace! Based on your request, I've selected some premium desktop items that would fit beautifully into a high-performance setup:";
        }

        return response()->json([
            'response' => $text,
            'recommendations' => $recommendedProducts,
            'vibeSuggested' => $vibe
        ]);
    }
}
