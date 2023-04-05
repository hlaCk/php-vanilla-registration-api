<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date as date;

/**
 * Class User
 *
 * @property       string                         $app_token
 * @property-read  string|\Carbon\CarbonInterface $created_at         {@type date}
 * @property       string                         $email
 * @property       string                         $email2
 * @property-read  string                         $email2_verified_at {@type date}
 * @property-read  string                         $email_verified_at  {@type date}
 * @property-read  int                            $id
 * @property string                               $password
 * @property       string                         $remember_token
 * @property       string                         $session
 * @property-read  string                         $updated_at         {@type date}
 *
 */
class User extends AbstractModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'email2',
        'password',
        'remember_token',
        'email_verified_at',
        'email2_verified_at',
        'remember_token',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public static function deleteUnverified(): void
    {
        if( verificationStatus() ) {
            User::query()
                ->where('created_at', "<", verifySubMinutes())
                ->where(fn($q) => $q->whereNull('email_verified_at')->orWhereNull('email2_verified_at'))
                ->get()
                ->each
                ->delete();
        }
    }

    /**
     * @param string|array $email
     *
     * @return void
     */
    public static function deleteUnverifiedByEmail($email): void
    {
        static::query()
              ->whereIn('email', array_wrap($email))
              ->where(fn($q) => $q->whereNull('email_verified_at')->orWhereNull('email2_verified_at'))
              ->get()
            ->each
            ->delete();
    }

    protected static function boot(): void
    {
        parent::boot();
        parent::deleting(function(User $user) {
            $user->password_resets()->delete();

            return $user;
        });
    }

    public function sessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function password_resets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PasswordReset::class);
    }

    public function createPasswordReset(): ?PasswordReset
    {
        if( $email = $this->getEmailForVerification(true) ) {
            $model = PasswordReset::make([
                                             'user_id' => $this->id,
                                             'email' => $email,
                                             'token' => str_random(8),
                                             'created_at' => now(),
                                         ]);

            return $this->password_resets()->save($model);
        }

        return null;
    }

    /**
     * @return \App\Models\Session
     */
    public function getSession(): Session
    {
        return $this->sessions()->forMe($this->id)->firstOr(fn() => $this->createNewSeesion());
    }

    /**
     * @return false|\Illuminate\Database\Eloquent\Model
     */
    public function createNewSeesion(): \Illuminate\Database\Eloquent\Model|bool
    {
        return $this->sessions()->save(Session::makeSession($this->id));
    }

    /**
     * @return bool|null
     */
    public function deleteSession(): ?bool
    {
        return $this->getSession()->delete();
    }

    /**
     * @return bool|null
     */
    public function deleteSessions(): ?bool
    {
        return $this->sessions()->delete();
    }

    /**
     * @param bool $single
     *
     * @return array|string|null
     */
    public function getEmailForVerification(bool $single = false): array|string|null
    {
        if( $single ) {
            return $this->email && !$this->email_verified_at ? $this->email :
                ($this->email2 && !$this->email2_verified_at ? $this->email2 : null);
        }

        $email = [];
        if( $this->email && !$this->email_verified_at ) {
            $email[] = [ 'email' => $this->email, 'name' => $this->name ];
        } else {
            if( $this->email2 && !$this->email2_verified_at ) {
                $email[] = [ 'email' => $this->email2, 'name' => $this->name ];
            }
        }

        return empty($email) ? null : $email;
    }

    public function scopeByEmail(Builder $builder, $email): Builder
    {
        return $builder->whereIn('email', array_wrap($email));
    }

    public function scopeByEmail2(Builder $builder, $email): Builder
    {
        return $builder->whereIn('email2', array_wrap($email));
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at) && (
                $this->email2 && !is_null($this->email2_verified_at) || !$this->email2
            );
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified(string $column = null): bool
    {
        $data = [];
        if( $column ) {
            $email = in_array($column, [ 'email', 'email2' ]) ? $this->$column : $column;
            if( $email ) {
                if( $this->email === $email ) {
                    $data[ 'email_verified_at' ] = $this->freshTimestamp();
                } else {
                    $data[ 'email2_verified_at' ] = $this->freshTimestamp();
                }
            }
        }

        if( empty($data) ) {
            if( $this->email2 && !$this->email2_verified_at ) {
                $data[ 'email2_verified_at' ] = $this->freshTimestamp();
            } elseif( $this->email && !$this->email_verified_at ) {
                $data[ 'email_verified_at' ] = $this->freshTimestamp();
            }
        }

        if( !$this->email2 ) {
            $data[ 'email2_verified_at' ] = $this->freshTimestamp();
        }

        return $this->forceFill($data)->save();
    }

    /**
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendEmailVerification(?string $title = null, string|\Closure|null $message = null): bool
    {
        $password_reset = $this->createPasswordReset();

        $title = $title ?: __('Verify Email Address');
        $email = $password_reset?->email;

        return mailer(
            config('app.name', "Site") . ' - ' . $title . ' - ' . $email,
            $message ?: fn($data) => view('mail', $data),
            $email,
            [
                'name' => $this->name ?: $email,
                'hash' => $password_reset?->token,
            ]
        );
    }

    /**
     * @param string|null          $title
     * @param string|\Closure|null $message
     *
     * @return \Bootstrap\Container\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function viewEmailVerification($token = null): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Bootstrap\Container\Application
    {
        $email = $this->getEmailForVerification();

        return view('mail', [
            'name' => data_get(head($email), 'name') ?:
                data_get(head($email), 'email') ?:
                    data_get($email, 'name') ?:
                        data_get($email, 'email') ?:
                            $this->email,
            'hash' => $token,
        ]);
    }

    public function isPast(): bool
    {
        return $this->created_at
            ->addMinutes(config('mail.verification.expire', 60))
            ->isPast();
    }
}
