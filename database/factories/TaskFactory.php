<?php

/* @var $factory Factory */

use App\Task;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(Task::class, function (Faker $faker) {
    return [
        'name'=>$faker->sentence,
    ];
});

$factory->state(Task::class, 'completed', [
    'completed_at' => Carbon::parse('-1day')
]);
