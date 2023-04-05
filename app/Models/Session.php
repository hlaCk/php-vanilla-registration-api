<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class Session
 *
 * @property-read  int    $id
 * @property       string $ip_address
 * @property       string $last_activity
 * @property       string $payload
 * @property       string $user_agent
 * @property-read  int    $user_id
 *
 */
class Session extends AbstractModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'token',
        'last_activity',
        'user_agent',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param int         $user_id
     * @param string|null $token
     *
     * @return \App\Models\Session
     */
    public static function makeSession(int $user_id, ?string $token = null)
    {
        return static::make([
                                'user_id' => $user_id,
                                'ip_address' => request()->getClientIp(),
                                'token' => $token ?: request()->get('token', str_random(5)),
                                'last_activity' => now(),
                                'user_agent' => request()->userAgent(),
                            ]);
    }

    /**
     * @param int         $user_id
     * @param string|null $token
     *
     * @return \App\Models\Session
     */
    public static function createSession(int $user_id, ?string $token = null): Session
    {
        $model = static::makeSession($user_id, $token);
        $model->save();

        return $model;
    }

    public function deleteOtherSessions(): Session
    {
        static::byUser($this->user_id)
              ->whereKeyNot($this->id)
              ->get()
            ->each
            ->delete();

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param                                       $user_id
     *
     * @return \Illuminate\Database\Eloquent\Builder|mixed
     */
    public function scopeForMe(Builder $builder, $user_id = null)
    {
        return $builder->where('ip_address', request()->getClientIp())
                       ->where('user_agent', request()->userAgent())
                       ->when($user_id, fn($q) => $q->where('user_id', $user_id));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param                                       $token
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForToken(Builder $builder, $token = null)
    {
        return $builder->where('ip_address', request()->getClientIp())
                       ->where('user_agent', request()->userAgent())
                       ->where('token', $token ?: request('token'));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param int|array|\App\Models\User            $user
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser(Builder $builder, $user = null)
    {
        $user ??= currentUser();
        $user = is_numeric($user) ? User::find($user) : $user;
        $user = array_wrap($user);

        /** @var \App\Models\User $u */
        foreach( $user as $key => $u ) {
            if( $u instanceof User ) {
                $user[ $key ] = $u->id;
            }
        }

        return $builder->whereIn('user_id', array_wrap($user));
    }
}
