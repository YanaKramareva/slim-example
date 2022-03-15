<?php

namespace App;

class SchoolRepository
{
    private array $schools;

    public function __construct()
    {
        $this->file = file_get_contents('schools.json');
        $this->schools = json_decode($this->file, true);
    }

    public function all()
    {
        return $this->schools;
    }

    public function find(string $id)
    {
        return collect($this->schools)->firstWhere('id', $id);
    }

    public function save(array $school): void
    {
        $this->school['id'] = uniqid();
        $this->school['name'] = $school['name'];
        $this->schools [] = $this->school;
        file_put_contents('schools.json', json_encode($this->schools));
    }
    public function edit(array $school): void
    {
        $this->school = $this->find($school['id']);
        $this->school['name'] = $school['name'];
        $this->schools[] = $this->school;
        file_put_contents('schools.json', json_encode($this->schools));
    }

    public function destroy(array $school): void
{
    $this->school = $this->find($school['id']);
    $this->school['id'] = '';
    $this->school['name'] = '';
    $this->schools[] = $this->school;
    file_put_contents('schools.json', json_encode($this->schools));
}
}
