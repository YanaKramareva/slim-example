<?php

namespace App;

class PostValidator
{
    public function validate($post)
    {
        $errors = [];
        if (empty($post['name'])) {
            $errors['name'] = "Can't be blank name";
        }
        if (empty($post['body'])) {
            $errors['body'] = "Can't be blank body";
        }
        return $errors;
    }
}
