<?php

namespace App\Repositories;

use App\Post;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Get's a post by it's ID
     *
     * @param int
     * @return collection
     */
    public function get($post_id)
    {
        return Post::find($post_id);
    }


    /**
     * Get's posts from one user  by it's ID
     *
     * @param int
     * @return collection
     */
    public function getOneUserPosts($user_id)
    {
        return Post::where('user_id','=',$user_id)->select('id','title', 'created_at', 'updated_at')->latest()->get();
    }

    /**
     * Get's all posts.
     *
     * @return mixed
     */
    public function all()
    {
        return Post::all();
    }

    /**
     * Deletes a post.
     *
     * @param int
     */
    public function delete($post_id)
    {
        Post::destroy($post_id);
    }

    /**
     * Create a post.
     *
     * @param array
     */
    public function create(array $post_data)
    {    	
        Post::create($post_data);
    }

    /**
     * Updates a post.
     *
     * @param int
     * @param array
     */
    public function update($post_id, array $post_data)
    {
        Post::find($post_id)->update($post_data);
    }

    
}