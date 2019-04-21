<?php

namespace Tests\Feature;

use App\Task;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateTaskTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     */
    public function aUserCanCreateANewTask()
    {
        $user = factory(User::class)->create();
        $taskData = [
            'name'=>'A New Task',
            'api_token'=>$user->api_token
        ];

        $this->post('/api/tasks', $taskData)
            ->assertJson([
                [
                    'name'=>'A New Task',
                    'user_id'=>$user->id,
                ]
            ]);
        $this->assertDatabaseHas('tasks', [
            'name'=>'A New Task',
            'user_id'=>$user->id,
        ]);
    }

    /**
     * @test
     */
    public function aUserCanCreateASubTask()
    {
        $user = factory(User::class)->create();
        $parentTask = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $taskData = [
            'name'=>'A New Child Task',
            'parent_id'=>$parentTask->id,
            'api_token'=>$user->api_token
        ];

        $this->post('/api/tasks', $taskData)
            ->assertJson([
                [
                    'name'=>'A New Child Task',
                    'parent_id'=>$parentTask->id
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'name'=>'A New Child Task',
            'parent_id'=>$parentTask->id
        ]);
    }

    /**
     * @test
     */
    public function aUserCanCreateMultipleTasksAtOnce()
    {
        $user = factory(User::class)->create();
        $taskData = [
            'tasks'=>[
                [
                    'name'=>'Task 1',
                    'parent_id'=>null
                ],
                [
                    'name'=>'Task 2',
                    'parent_id'=>null
                ]
            ],
            'api_token'=>$user->api_token
        ];

        $this->post('/api/tasks', $taskData)
            ->assertJson([
                [
                    'name'=>'Task 1',
                    'parent_id'=>null
                ],
                [
                    'name'=>'Task 2',
                    'parent_id'=>null
                ]
            ]);
        foreach ($taskData['tasks'] as $task) {
            $this->assertDatabaseHas('tasks',[
                'name'=>$task['name'],
                'parent_id'=>null
            ]);
        }
    }

    /**
     * @test
     */
    public function aUserCanNotCreateASubTaskOfAnotherUsersTask()
    {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();
        $parentTask = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $taskData = [
            'name'=>'A New Child Task',
            'parent_id'=>$parentTask->id,
            'api_token'=>$user2->api_token
        ];

        $this->post('/api/tasks', $taskData)
            ->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'name'=>'A New Child Task',
            'parent_id'=>$parentTask->id
        ]);
    }
}
