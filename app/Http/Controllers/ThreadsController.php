<?php

namespace App\Http\Controllers;

use App\Filters\ThreadFilters;
use App\Channel;
use App\Thread;
use Illuminate\Http\Request;

class ThreadsController extends Controller
{
    /**
     * Create a new ThreadsController instance.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Channel      $channel
     * @param ThreadFilters $filters
     * @return \Illuminate\Database\Eloquent\Collection | \Illuminate\Http\Response
     */
    public function index(Channel $channel, ThreadFilters $filters)
    {
        $threads = $this->getThreads($channel, $filters);

        if (request()->wantsJson()) {
            return $threads;
        }

        return view('threads.index', [ 'threads' => $threads ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('threads.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation
        $this->validate($request, [
            'channel_id' => 'required|exists:channels,id',
            'title' => 'required',
            'body' => 'required',
        ]);

        $thread = Thread::create([
            'user_id' => auth()->id(),
            'channel_id' => $request->channel_id,
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect($thread->path())
            ->with('flash', 'Your thread has been published!');
    }

    /**
     * Display the specified resource.
     *
     * @param  integer      $channel
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function show($channelId, Thread $thread)
    {
        return view('threads.show', [ 
            'thread' => $thread->append('isSubscribedTo'),
            // 'replies' => $thread->replies()->paginate(20) // Now fetched by vue component
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function edit(Thread $thread)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Thread $thread)
    {
        //
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  integer      $channelId
     * @param  \App\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function destroy($channelId, Thread $thread)
    {
        // Check user permissions

        // Option #1
        // Check if user tryes to delete other user's thread
        // if($thread->user_id != auth()->id()) {
            // if (request()->wantsJson()) {
            //     return response(['Status' => 'Permission denied'], 403);
            // } else {
            //     return redirect('/login');
            // }
        // }

        // Option #2
        // if($thread->user_id != auth()->id()) {
        //     abort(403, 'You have no permission to do that!');
        // }

        // Option #3. Auth Policy: ThreadPolicy
        $this->authorize('update', $thread);

        // $thread->replies()->delete();
        // Replaced by model::event
        $thread->delete();

        if (request()->wantsJson()) {
            return response([], 204);
        }
        
        return redirect('/threads')
            ->with('flash', 'Your thread has been deleted!');
    }

    /**
     * Fetch all relevant threads.
     *
     * @param Channel       $channel
     * @param ThreadFilters $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getThreads($channel, $filters)
    {
        $threads = Thread::filter($filters)->latest();
        
        if ($channel->exists) {
            $threads = $threads->where('channel_id', $channel->id);
        }

        // Check actual SQL query generated by query builder
        // dd($threads->toSql());

        return $threads->get();
    }
}