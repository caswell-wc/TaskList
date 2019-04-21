<?php

namespace Tests\Feature;

use App\Task;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ListTasksTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     */
    public function aUserCanGetAListOfTheirTasks()
    {
        $user = factory(User::class)->create();
        $task1 = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $task2 = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);

        $this->get('/api/tasks?api_token=' . $user->api_token)
            ->assertJson([
                [
                    'id'=>$task1->id,
                    'name'=>$task1->name
                ],
                [
                    'id'=>$task2->id,
                    'name'=>$task2->name
                ]
            ]);
    }

    /**
     * @test
     */
    public function aUserCanNotSeeOtherUsersTasks()
    {
        $user = factory(User::class)->create();
        $task1 = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $task2 = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $user2 = factory(User::class)->create();
        $task3 = factory(Task::class)->create([
            'user_id'=>$user2->id
        ]);

        $this->get('/api/tasks?api_token=' . $user->api_token)
            ->assertJson([
                [
                    'id'=>$task1->id,
                    'name'=>$task1->name
                ],
                [
                    'id'=>$task2->id,
                    'name'=>$task2->name
                ]
            ])
            ->assertJsonMissing([
                'id'=>$task3->id,
                'name'=>$task3->name
            ]);
    }
}
