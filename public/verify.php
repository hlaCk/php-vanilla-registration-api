<?php

use App\Helpers\Response;

require_once 'loader.php';

try {
    $instance = PasswordResetForToken();
    $user = UserForToken();
    if( !$user || !$instance )
    {
        throw new Exception("invalid_link");
    }
} catch(Exception $exception) {
    return Response::make(0, 'invalid_link');
}

$result = 0;
if( !$user->hasVerifiedEmail() ) {
    $user->markEmailAsVerified($instance->email);
    $instance && $instance->delete();
    !$user->hasVerifiedEmail() && $user->sendEmailVerification();
    $result = 1;
}

return Response::make($result);