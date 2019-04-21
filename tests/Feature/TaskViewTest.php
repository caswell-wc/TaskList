<?php

namespace Tests\Feature;

use App\Task;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskViewTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     */
    public function aTaskCanBeViewed()
    {
        $user = factory(User::class)->create();
        $task = new Task();
        $task->name = 'My Sample Task';
        $task->user()->associate($user);
        $task->save();

        $this->get('/api/tasks/' . $task->id . '?api_token=' . $user->api_token)
            ->assertJson([
                'name'=>'My Sample Task',
            ]);
    }

    /**
     * @test
     */
    public function aUserCanOnlyViewTheirOwnTask()
    {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();
        $task = new Task();
        $task->name = 'My Sample Task';
        $task->user()->associate($user1);
        $task->save();

        $this->get('/api/tasks/' . $task->id . '?api_token=' . $user2->api_token)
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function aTaskViewContainsChildren()
    {
        $user = factory(User::class)->create();
        $parentTask = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $children = factory(Task::class, 2)->create([
            'user_id'=>$user->id,
            'parent_id'=>$parentTask->id
        ]);

        $this->get('/api/tasks/' . $parentTask->id . '?api_token=' . $user->api_token)
            ->assertJson([
                'id'=>$parentTask->id,
                'children'=>$children->toArray()
            ]);
    }
}
