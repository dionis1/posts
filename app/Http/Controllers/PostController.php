<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PostRepositoryInterface;
use App\Post;
use App\User;
use App\Http\Requests\PostRequest;

class PostController extends Controller
{

    protected $postData;

    /** 
     * PostController constructor.
     *
     * Check auth middleware
     * @param PostRepositoryInterface $post
     */
    public function __construct(PostRepositoryInterface $postData)
    {
        $this->postData = $postData;
        $this->middleware('auth');
    }

	public function index(Request $request){
        
    	return view('posts.index');
    }

    public function show(Post $post){
        
        // implements PostRepositoryInterface 
        // method get() except post id and return one post

        $data = $this->postData->get($post->id);

    	return view('posts.show', compact('post'));

    }

    public function create(){


    	return view('posts.create');

    }

    public function store(PostRequest $request){
        
        $validated = $request->validated();

        // implements PostRepositoryInterface 
        // method create() except  request data 

        $post = $this->postData->create(
            array_merge($validated,
                [
                    'user_id'=>auth()->user()->id
                ])
        );
            
    	return 'success';

    }

    public function edit(Post $post){

        $this->authorize('update',$post);

    	return view('posts.edit',compact('post'));

    }

    public function update(PostRequest $request, Post $post){

        $this->authorize('update',$post);

        $validated = $request->validated();

        // implements PostRepositoryInterface 
        // method update() except post id and request data 

        $posts = $this->postData->update($post->id,$validated);

    	return 'success';

    }
    public function delete(Post $post){

        $this->authorize('delete',$post);
        $post->delete();

        return redirect()->back()->with('message', 'Your posts is deleted!');

    }

    public function dataForDataTable()
    {

        // implements PostRepositoryInterface 
        // method all() returns all posts

        $posts = $this->postData->all();

        return datatables()->of($posts)
            ->addColumn('name', function($row){
                $user = User::where('id',$row->user_id)->first();
                return $user['name'];
            })
            ->make(true);

    }

    public function dataFromOneUserForDataTable()
    {

        // implements PostRepositoryInterface 
        // method getOneUserPosts() returns all posts from the user 

        $posts = $this->postData->getOneUserPosts(auth()->user()->id);

        return datatables()->of($posts)
            ->make(true);

    }
}
