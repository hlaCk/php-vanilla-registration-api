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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public static function createSession(int $user_id, ?string $token = null)
    {
        $model = static::makeSession($user_id,$token);
        $model->save();

        return $model;
    }

    public function scopeForMe(Builder $builder, $user_id = null)
    {
        return $builder->where('ip_address', request()->getClientIp())
                       ->where('user_agent', request()->userAgent())
                       ->when($user_id, fn($q)=>$q->where('user_id', $user_id));
    }

    public function scopeForToken(Builder $builder, $token = null)
    {
        return $builder->where('ip_address', request()->getClientIp())
                       ->where('user_agent', request()->userAgent())
                       ->where('token', $token ?: request('token'));
    }
}
