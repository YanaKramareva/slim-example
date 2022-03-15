<?php

namespace App;

class UserRepository
{
    private $users;
    private $user;
    private $file;

    public function __construct()
    {
        $this->file = file_get_contents('user.json');
        $this->users = json_decode($this->file, true);
        $this->courses = ['mi', 'shel', 'ad', 'kek', 'kam'];
        $this->user = [];
    }

    public function all()
    {
        return $this->users;
    }

    public function find(string $id)
    {
        return collect($this->users)->firstWhere('id', $id);
    }

    public function save(array $user): void
    {
        $this->user = $user;
        $this->user['id'] = uniqid();
        $this->users [] = $this->user;
        file_put_contents('user.json', json_encode($this->users));
    }
}
