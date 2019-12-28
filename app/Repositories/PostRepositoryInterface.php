<?php

namespace App\Repositories;

interface PostRepositoryInterface
{

	/**
     * Get's a post by it's ID
     *
     * @param int
     */
    public function get($post_id);

     /**
     * Get's posts from one user  by it's ID
     *
     * @param int
     */
    public function getOneUserPosts($user_id);

    /**
     * Get's all posts.
     *
     * @return mixed
     */
    public function all();

    /**
     * Deletes a post.
     *
     * @param int
     */
    public function delete($post_id);

    /**
     * Create a post.
     *
     * @param array
     */
    public function create(array $post_data);


    /**
     * Updates a post.
     *
     * @param int
     * @param array
     */
    public function update($post_id, array $post_data);

}