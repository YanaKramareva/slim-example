<?php

namespace App;

class Validator
{
    public function validate($user)
    {
        $errors = [];
        if (empty($user['name']) || empty($user['email']) || empty($user['password']) || empty($user['city'])) {
            $errors['name'] = "Can't be blank";
        }

        return $errors;
    }
}
