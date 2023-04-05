<?php

use App\Helpers\Response;

require_once 'loader.php';

if( request()->has([ 'token' ]) ) {
    $user = requireToken();
} elseif( request()->has([ 'email', 'password' ]) ) {
    /** @var \App\Models\User $user */
    $user = \App\Models\User::byEmail(request('email'))->firstOrFail();

    if( !password_verify(request('password'), $user->password) ) {
        return \App\Helpers\Response::make(0, 'user_not_found');
    }

    if( verificationStatus() && !$user->hasVerifiedEmail() ) {
        return \App\Helpers\Response::make(0, 'user_not_found');
    }
} else {
    return Response::make(0, 'miss_param');
}

$session = $user->getSession()->deleteOtherSessions();

return Response::make($session->token);