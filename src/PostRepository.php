<?php

namespace App;

class PostRepository
{
    private $posts;
    private $post;
    private $file;

    public function __construct()
    {
        $this->file = file_get_contents('posts.json');
        $this->posts = json_decode($this->file, true);
    }

    public function all()
    {
        return $this->posts;
    }

    public function find(string $id)
    {
        return collect($this->posts)->firstWhere('id', $id);
    }

    public function save(array $post): void
    {
        $this->post = $post;
        $this->post['id'] = uniqid();
        $this->posts [] = $this->post;
        file_put_contents('posts.json', json_encode($this->posts));
    }
    public function destroy(array $post): void
    {
        $this->post = $this->find($post['id']);
        $this->post['id'] = '';
        $this->post['name'] = '';
        $this->post['body'] = '';
        $this->posts [] = $this->post;
        file_put_contents('posts.json', json_encode($this->posts));
    }
}
