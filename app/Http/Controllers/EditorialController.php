<?php
namespace App\Http\Controllers;
use App\Enums\PostStatus;
use App\Http\Requests\PostInput;
use App\Models\{Post,PostRevision,Category,Tag,Media};
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;
class EditorialController extends Controller
{
    public function index(Request $request)
    {
        $filters=$request->validate(['q'=>'nullable|string|max:100','status'=>['nullable',Rule::enum(PostStatus::class)],'category'=>'nullable|integer|exists:categories,id']);
        $base=Post::visibleTo($request->user());
        $counts=(clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $posts=$base->with(['author','category'])->when($request->filled('q'),fn($q)=>$q->search($filters['q']))
            ->when($request->filled('status'),fn($q)=>$q->where('status',$filters['status']))
            ->when($request->filled('category'),fn($q)=>$q->where('category_id',$filters['category']))->latest('updated_at')->orderByDesc('id')->paginate(12)->withQueryString();
        return view('posts.index',compact('posts','counts')+['categories'=>Category::orderBy('name')->get()]);
    }
    public function create()
    {
        Gate::authorize('create',Post::class);
        return view('posts.edit',$this->editorData(new Post(['locale'=>app()->getLocale()])));
    }
    public function store(PostInput $request,PostService $service)
    {
        Gate::authorize('create',Post::class);
        $post=$service->create($request->user(),$request->validated());
        return redirect()->route('posts.edit',$post)->with('status',__('Draft created.'));
    }
    public function edit(Post $post)
    {
        Gate::authorize('view',$post);
        $post->load(['tags','author']);
        return view('posts.edit',$this->editorData($post));
    }
    private function editorData(Post $post): array
    {
        return ['post'=>$post,'categories'=>Category::orderBy('name')->get(),'tags'=>Tag::orderBy('name')->get(),
            'media'=>Media::where('status','ready')->where('user_id',auth()->id())->latest()->limit(100)->get()];
    }
    public function update(PostInput $request,Post $post,PostService $service)
    {
        Gate::authorize('update',$post);
        $request->validate(['lock_version'=>'required|integer|min:1']);
        $service->update($request->user(),$post,$request->validated(),(int)$request->lock_version);
        return redirect()->route('posts.edit',$post)->with('status',__('Changes saved.'));
    }
    public function transition(Request $request,Post $post,PostService $service)
    {
        $data=$request->validate(['target'=>['required',Rule::enum(PostStatus::class)],'lock_version'=>'required|integer|min:1','feedback'=>'nullable|string|max:2000','scheduled_at'=>'nullable|date']);
        $service->transition($request->user(),$post,$data['target'],(int)$data['lock_version'],$data['feedback']??null,$data['scheduled_at']??null);
        return back()->with('status',__('Workflow updated.'));
    }
    public function revisions(Request $request,Post $post)
    {
        Gate::authorize('view',$post);
        $revisions=$post->revisions()->with('author')->orderByDesc('version')->paginate(20);
        $left=$post->revisions()->when($request->filled('left'),fn($q)=>$q->whereKey($request->integer('left')))->orderByDesc('version')->first();
        $right=$post->revisions()->when($request->filled('right'),fn($q)=>$q->whereKey($request->integer('right')),fn($q)=>$q->where('version','<',$left?->version??0))->orderByDesc('version')->first();
        return view('posts.revisions',compact('post','revisions','left','right'));
    }
    public function restore(Request $request,Post $post,PostRevision $revision,PostService $service)
    {
        $data=$request->validate(['lock_version'=>'required|integer|min:1']);
        abort_unless($revision->post_id===$post->id,404);
        $service->restore($request->user(),$post,$revision,(int)$data['lock_version']);
        return redirect()->route('posts.edit',$post)->with('status',__('Revision restored as a new draft.'));
    }
    public function preview(Request $request)
    {
        $data=$request->validate(['body'=>'required|string|max:100000']);
        $post=new Post(['body'=>$data['body']]);
        return response()->json(['html'=>$post->renderedBody()]);
    }
    public function activity(Post $post)
    {
        Gate::authorize('view',$post);
        return view('posts.activity',['post'=>$post,'activities'=>$post->activities()->with('actor')->latest('created_at')->paginate(30)]);
    }
    public function calendar(Request $request)
    {
        $request->validate(['month'=>'nullable|date_format:Y-m']);
        $month=CarbonImmutable::createFromFormat('!Y-m',$request->input('month',now()->format('Y-m')));
        $posts=Post::visibleTo($request->user())->whereIn('status',['scheduled','published'])
            ->where(function($q) use($month){$q->whereBetween('scheduled_at',[$month,$month->endOfMonth()])->orWhereBetween('published_at',[$month,$month->endOfMonth()]);})->with('author')->get();
        return view('posts.calendar',compact('month','posts'));
    }
}
