<?php

use App\Helpers\Response;

require_once 'loader.php';

if( $user = requireToken() ) {
  return Response::make($user->deleteSession(), 'success');
}

return Response::make(0, 'miss_param');