<?php

use App\Helpers\Response;
use App\Models\PasswordReset;
use App\Models\Session;
use Illuminate\Contracts\Routing\UrlGenerator;

if( !function_exists('request') ) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param array|string|null $key
     * @param mixed             $default
     *
     * @return mixed|\Illuminate\Http\Request|string|array|null
     */
    function request($key = null, $default = null)
    {
        if( is_null($key) ) {
            return app('request');
        }

        if( is_array($key) ) {
            return app('request')->only($key);
        }

        $value = app('request')->__get($key);

        return is_null($value) ? value($default) : $value;
    }
}

if( !function_exists('currentUser') ) {
    /**
     * @return \App\Models\User|null
     */
    function currentUser()
    {
        /** @var \App\Models\Session $session */
        $session = Session::forToken()->first();

        return $session?->user()->with('sessions')->first();
    }
}

if( !function_exists('url') ) {
    /**
     * Generate a url for the application.
     *
     * @param string|null $path
     * @param mixed       $parameters
     * @param bool|null   $secure
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        if( is_null($path) ) {
            return app(UrlGenerator::class);
        }

        return app(UrlGenerator::class)->to($path, $parameters, $secure);
    }
}

if (! function_exists('asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    function asset($path, $secure = null)
    {
        return app('url')->asset($path, $secure);
    }
}

if( !function_exists('requireToken') ) {
    /**
     * @return \App\Models\User
     */
    function requireToken(): \App\Models\User
    {
        if( !request()->has('token') || !($user = currentUser()) ) {
            return Response::make(0, 'login_first');
        }

        return $user;
    }
}

if( !function_exists('verifyPassword') ) {
    /**
     * @param \App\Models\User|null $user
     *
     * @return bool
     * @throws \Exception
     */
    function verifyPassword(\App\Models\User|null $user = null, string|null $password = null): bool
    {
        $user = $user ?: requireToken();
        if( !password_verify($password ?: request('password'), $user->password) ) {
            throw new Exception("wrong_password");
        }

        return true;
    }
}

if( !function_exists('PasswordResetForToken') ) {
    /**
     * @param string|null $token
     *
     * @return \App\Models\PasswordReset|null
     */
    function PasswordResetForToken(string|null $token = null): ?PasswordReset
    {
        return PasswordReset::byToken($token ?? request('hash', request('token')))->first();
    }
}

if( !function_exists('UserForToken') ) {
    /**
     * @param string|null $token
     *
     * @return \App\Models\User|null
     */
    function UserForToken(string|null $token = null): \App\Models\User|null
    {
        return PasswordResetForToken($token)?->user;
    }
}

if( !function_exists('__') ) {
    /**
     * Translate the given message.
     *
     * @param string|null $key
     * @param array       $replace
     * @param string|null $locale
     *
     * @return string|array|null
     */
    function __($key = null, $replace = [], $locale = null)
    {
        if( is_null($key) ) {
            return $key;
        }

        return trans($key, $replace, $locale);
    }
}

if( !function_exists('trans') ) {
    /**
     * Translate the given message.
     *
     * @param string|null $key
     * @param array       $replace
     * @param string|null $locale
     *
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function trans($key = null, $replace = [], $locale = null)
    {
        if( is_null($key) ) {
            return app('translator');
        }

        return app('translator')->get($key, $replace, $locale);
    }
}

if( !function_exists('trans_choice') ) {
    /**
     * Translates the given message based on a count.
     *
     * @param string               $key
     * @param \Countable|int|array $number
     * @param array                $replace
     * @param string|null          $locale
     *
     * @return string
     */
    function trans_choice($key, $number, array $replace = [], $locale = null)
    {
        return app('translator')->choice($key, $number, $replace, $locale);
    }
}
