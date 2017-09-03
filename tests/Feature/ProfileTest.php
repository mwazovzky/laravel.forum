<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProfileTest extends TestCase
{
    use DatabaseMigrations;     

    /** @test */
    public function a_user_has_a_profile()
    {
        $user = create('App\User');

        $this->get('/profiles/' . $user->name)
            ->assertSee($user->name);
    }

    /** @test */
    public function profiles_display_all_threads_created_by_associated_user()
    {
        $user = create('App\User');
        $threadByUser = create('App\Thread', ['user_id' => $user->id]);
        $threadNotByUser = create('App\Thread');

        $this->get('/profiles/' . $user->name)
            ->assertSee($threadByUser->title)
            ->assertSee($threadByUser->body)
            ->assertDontSee($threadNotByUser->title);
    }    
}