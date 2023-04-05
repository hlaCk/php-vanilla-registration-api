<?php

namespace Database\Factories;

use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 *
 */
class SessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Session::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => UserFactory::new(),
            'ip_address'=> $this->faker->ipv4(),
            'token' => Str::random(5),
            'last_activity'=> $this->faker->dateTime(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

}
