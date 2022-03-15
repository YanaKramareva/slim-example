<?php

namespace App;

use Faker\Factory;

class Generator
{
    public static function generate($count)
    {
        $numbers = range(1, $count);
        shuffle($numbers);

        $faker = Factory::create();
        $faker->seed(1);
        $posts = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = [
                'id' => $faker->uuid,
                'name' => $faker->text(70),
                'body' => $faker->sentence,
                'slug' => $faker->slug
            ];
        }

        return $users;
    }
}
