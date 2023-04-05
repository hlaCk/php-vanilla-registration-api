<?php

use App\Helpers\Response;

require_once 'loader.php';
$user = requireToken();

Response::make($user ? 1 : 0, 'success');