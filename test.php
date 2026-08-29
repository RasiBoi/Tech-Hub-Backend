<?php echo json_encode(\App\Models\Product::select(['id', 'title', 'category_id', 'vendor_id'])->get());
