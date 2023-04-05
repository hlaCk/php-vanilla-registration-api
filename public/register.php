<?php

use App\Helpers\Response;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

require_once 'loader.php';

User::query()
    ->where('created_at', "<", verifySubMinutes())
    ->where(fn($q) => $q->whereNull('email_verified_at')->orWhereNull('email2_verified_at'))
    ->get()
    ->each
    ->delete();

try {
    if( request()->has('email') ) {
        if( $user = \App\Models\User::byEmail(request('email'))->first() ) {
            if( $user->created_at
                ->addMinutes(config('mail.verification.expire', 60))
                ->isPast() ) {
                $user->delete();
            }
        }
    }
    
    $data = Validator::validate(request()->all(), [
        'email' => [
            'required',
            'string',
            'email:filter',
            Rule::unique('users', 'email')
                ->where(fn($q) => $q->where('created_at', ">", verifySubMinutes())
                                    ->orWhere(fn($q2) => $q2->whereNotNull([ 'email_verified_at', 'email2_verified_at' ]))),
        ],
        'email2' => [ 'nullable', 'string', 'email:filter' ],
        'password' => [ 'required', 'string' ],
        'name' => [ 'nullable', 'string' ],
    ]);
} catch(ValidationException $exception) {
    if( request()->has('s-r') ) {
        dd(__LINE__, $exception);
    }

    return Response::make(0, $exception->getMessage());
}

User::where('email', $data[ 'email' ])
    ->where(fn($q) => $q->whereNull('email_verified_at')->orWhereNull('email2_verified_at'))
    ->get()
    ->each
    ->delete();

$data[ 'password' ] = bcrypt($data[ 'password' ]);
($user = \App\Models\User::make($data))->save();

// if( request()->has('s-r') ) {
//     return Response::make($user->viewEmailVerification('sdffdsf')->render(), 'success');
// }

if( config('mail.verification.status', false) ) {
    $result = $user->sendEmailVerification();

    if( request()->has('s-r') ) {
        dd(__LINE__, $result);
    }
} else {
    $result = $user->markEmailAsVerified();
    $result = $user->markEmailAsVerified();

    if( request()->has('s-r') ) {
        dd(__LINE__, $result);
    }
}

return Response::make($result, 'success');
