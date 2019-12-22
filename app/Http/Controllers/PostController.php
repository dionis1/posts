<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\User;

class PostController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

	public function index(Request $request){

        $posts = Post::all();
        
    	return view('posts.index',compact('posts'));
    }

    public function show(Post $post){


    	return view('posts.show', compact('post'));

    }

    public function create(){


    	return view('posts.create');

    }

    public function store(Request $request){
        

        $validatedData = $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        $post = auth()->user()->posts()->create($validatedData);
            
    	return $post;

    }

    public function edit(Post $post){

        $this->authorize('update',$post);

    	return view('posts.edit',compact('post'));

    }

    public function update(Request $request, Post $post){

        $this->authorize('update',$post);

        $validatedData = $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);


        $post->update($validatedData);

    	return 'success';

    }
    public function delete(Post $post){

        $this->authorize('delete',$post);
        $post->delete();

        return redirect()->back()->with('message', 'Your posts is deleted!');

    }

    public function dataForDataTable()
    {

         $posts = Post::select('id','title', 'created_at', 'updated_at', 'user_id')->with('user')->latest()->get();

        return datatables()->of($posts)
            ->addColumn('name', function($row){
                $user = User::where('id',$row->user_id)->first();
                return $user['name'];
            })
            ->make(true);

    }

    public function dataFromOneUserForDataTable()
    {

         $posts = Post::where('user_id','=',auth()->user()->id)->select('id','title', 'created_at', 'updated_at')->latest()->get();

        return datatables()->of($posts)
            ->make(true);

    }
}
