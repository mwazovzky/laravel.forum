<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use RecordsActivity;
    
    /**
     * Auto-apply mass assignment protection.
     *
     * @var array
     */
    protected $fillable = [ 'user_id', 'channel_id', 'title', 'body'];


    /**
     * The relationships to always eager-load.
     *
     * @var array
     */
    protected $with = ['user', 'channel'];


    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Global Query Scope
        // static::addGlobalScope('replyCount', function($builder) {
        //     return $builder->withCount('replies');
        // });
        // replies_count field added to database
        
        // Model events: delete associated replies when model:deleting event is fired off
        // 
        static::deleting(function($thread) {
            // $thread->replies()->delete();

            // The code above doesn't fire deleting event on reply model 
            // to delete asspciated reply activity, 
            // because reply models are not fetched,  
            // just a database query is creted to delete replies

            // Option 1. 
            // Fetch replies (models) and delete them
            // $thread->replies->each(function($reply) {
            //     $reply->delete();
            // });

            // Option 2. 
            // Higher order messaging on Laravel collection, use 'each' pseudo prop  
            $thread->replies->each->delete();
        });
    }
   

    /**
     * Get a string path for the thread.
     *
     * @return string
     */
    public function path()
    {
        // return '/threads/' . $this->channel->slug . '/' . $this->id;
        return "/threads/{$this->channel->slug}/{$this->id}";
    }

    /**
     * A thread belongs to a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A thread is assigned a channel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * A thread may have many replies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Add a reply to the thread.
     *
     * @param  array $reply
     * @return Model
     */
    public function addReply($reply)
    {
        return $this->replies()->create($reply);
    }

    /**
     * Apply all relevant thread filters.
     *
     * @param  Builder       $query
     * @param  ThreadFilters $filters
     * @return Builder
     */
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }

    /**
     * Subscribe a user to the current thread.
     *
     * @param  User|null $userId
     * @return $this
     */
    public function subscribe($user = null)
    {
        $this->subscriptions()->create([
            'user_id' => $user ? $user->id : auth()->id()
        ]);

        return $this;
    }

    /**
     * Unsubscribe a user from the current thread.
     *
     * @param User|null $userId
     */
    public function unsubscribe($user = null)
    {
        $this->subscriptions()
            ->where([ 'user_id' => $user ? $user->id : auth()->id() ])
            ->delete();
    }    

    /**
     * A thread can have many subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(ThreadSubscription::class);
    }

    /**
     * Check if a user is subscribed to the thread 
     *
     * @param User|null
     * @return bool
     */
    public function isSubscribedTo($user = null)
    {
        $user = $user ? $user : auth()->user();
        return $this->subscriptions()->where('user_id', $user->id)->exists();
    }

    /**
     * Attribute specifies if a user is subscribed to the thread 
     *
     * @param User|null
     * @return bool
     */
    public function getIsSubscribedToAttribute()
    {
        return $this->isSubscribedTo();
    }    
}
