<?php use App\Models\Product; use Illuminate\Support\Facades\Cache; $p = Product::find(30); if($p) { $p->update(["vendor_id" => 9]); } Cache::flush(); echo "Restored";
