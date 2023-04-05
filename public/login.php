<?php

use App\Helpers\Response;

require_once 'loader.php';

/** @var \App\Models\User $user */
if( request()->has([ 'token' ]) ) {
    $user = requireToken();
} elseif( request()->has([ 'email', 'password' ]) ) {
    $user = \App\Models\User::byEmail(request('email'))->firstOrFail();

    if( !password_verify(request('password'), $user->password) ) {
        return \App\Helpers\Response::make(0, 'user_not_found');
    }
} else {
    return Response::make(0, 'miss_param');
}

return Response::make($user->getSession()->token);