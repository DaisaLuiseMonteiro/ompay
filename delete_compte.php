<?php \App\\Models\\Compte::whereHas("client", function($q) { $q->where("email", "loudaisa02@gmail.com"); })->delete();
