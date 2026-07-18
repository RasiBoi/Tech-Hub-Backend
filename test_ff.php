<?php $arr = ["id"=>1, "name"=>"Test"]; $m = (new \App\Models\Category)->forceFill($arr); echo json_encode($m);
