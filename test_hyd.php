<?php $r = app(\App\Repositories\Contracts\ProductRepositoryInterface::class); $prods = $r->getFilteredProducts([]); echo json_encode(new \App\Http\Resources\ProductResource($prods->first()));
