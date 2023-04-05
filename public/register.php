<?php

use App\Helpers\Response;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

require_once 'loader.php';

User::deleteUnverified();

try {
    if( request()->has('email') ) {
        /** @var \App\Models\User $user */
        if( $user = \App\Models\User::byEmail(request('email'))->first() ) {
            if( $user->isPast() ) {
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
                ->when(verificationStatus(), function($query) {
                    return $query->where(fn($q) => $q
                        ->where('created_at', "<", verifySubMinutes())
                        ->orWhere(fn($q2) => $q2
                            ->whereNotNull([ 'email_verified_at', 'email2_verified_at' ])
                        )
                    );
                }),
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

User::deleteUnverifiedByEmail($data[ 'email' ]);

$data[ 'password' ] = bcrypt($data[ 'password' ]);
($user = \App\Models\User::make($data))->save();

// if( request()->has('s-r') ) {
//     return Response::make($user->viewEmailVerification('sdffdsf')->render(), 'success');
// }

if( verificationStatus() ) {
    $result = $user->sendEmailVerification();

    if( request()->has('s-r') ) {
        dd(__LINE__, $result);
    }
} else {
    $result = $user->markEmailAsVerified('email');
    $result = $user->markEmailAsVerified('email2');

    if( request()->has('s-r') ) {
        dd(__LINE__, $result);
    }
}

return Response::make($result, 'success');
