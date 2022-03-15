<?php

namespace App;

class SchoolValidator
{
    public function validate($school)
    {
        $errors = [];
        if (empty($school['name'])) {
            $errors['name'] = "Name can't be blank";
        }

        return $errors;
    }
}
