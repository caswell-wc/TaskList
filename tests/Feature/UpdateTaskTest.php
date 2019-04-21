<?php

namespace Tests\Feature;

use App\Task;
use App\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateTaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function aUserCanUpdateTheNameOnATask()
    {
        $user = factory(User::class)->create();

        $task = factory(Task::class)->create([
            'name' => 'Old Name',
            'user_id' => $user->id
        ]);

        $updateData = [
            'name' => 'New Name',
            'api_token' => $user->api_token
        ];

        $this->patch('/api/tasks/' . $task->id, $updateData)
            ->assertJson([
                'id' => $task->id,
                'name' => 'New Name',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'New Name'
        ]);
    }

    /**
     * @test
     */
    public function aUserCanNotUpdateTheNameOnADifferentUsersTask()
    {
        $user1 = factory(User::class)->create();
        $user2 = factory(User::class)->create();
        $task = factory(Task::class)->create([
            'name' => 'Old Name',
            'user_id' => $user1->id
        ]);

        $updateData = [
            'name'=>'New Name',
            'api_token'=>$user2->api_token
        ];

        $this->patch('/api/tasks/' . $task->id, $updateData)
            ->assertStatus(403);
        $this->assertDatabaseMissing('tasks', [
            'id'=>$task->id,
            'name' => 'New Name'
        ]);

    }

    /**
     * @test
     */
    public function aUserCanCompleteATask()
    {
        $user = factory(User::class)->create();

        $task = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);

        Carbon::setTestNow(Carbon::create(2019, 4, 19, 12));

        $expectedData = [
            'id' => $task->id,
            'completed_at' => '2019-04-19 12:00:00'
        ];
        $this->patch('/api/tasks/' . $task->id . '/toggle-complete?api_token=' . $user->api_token)
            ->assertJson($expectedData);

        $this->assertDatabaseHas('tasks', $expectedData);
    }

    /**
     * @test
     */
    public function aUserCanNotCompleteAnotherUsersTask()
    {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $task = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);

        Carbon::setTestNow(Carbon::create(2019, 4, 19, 12));

        $this->patch('/api/tasks/' . $task->id . '/toggle-complete?api_token=' . $user2->api_token)
            ->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id'=>$task->id,
            'completed_at'=>null
        ]);
    }

    /**
     * @test
     */
    public function aUserCanUncompleteATask()
    {
        $user = factory(User::class)->create();

        $task = factory(Task::class)->create([
            'user_id'=>$user->id,
            'completed_at'=>Carbon::now(),
        ]);

        $expectedData = [
            'id'=>$task->id,
            'completed_at'=>null
        ];
        $this->patch('/api/tasks/' . $task->id . '/toggle-complete?api_token=' . $user->api_token)
            ->assertJson($expectedData);
        $this->assertDatabaseHas('tasks', $expectedData);
    }

    /**
     * @test
     */
    public function aTaskWithIncompleteChildrenCannotBeCompleted()
    {
        $user = factory(User::class)->create();

        $parentTask = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);

        factory(Task::class)->state('completed')->create([
            'parent_id'=>$parentTask->id,
            'user_id'=>$user->id
        ]);
        factory(Task::class)->create([
            'parent_id'=>$parentTask->id,
            'user_id'=>$user->id
        ]);

        $this->patch('/api/tasks/' . $parentTask->id . '/toggle-complete?api_token=' . $user->api_token)
            ->assertStatus(409);
        $this->assertDatabaseHas('tasks', [
            'id'=>$parentTask->id,
            'completed_at'=>null
        ]);
    }

    /**
     * @test
     */
    public function aTaskCanGetMultipleChildrenAtOnce()
    {
        $user = factory(User::class)->create();
        $parentTask = factory(Task::class)->create([
            'user_id'=>$user->id,
        ]);

        $childrenTasks = factory(Task::class, 2)->create([
            'user_id'=>$user->id,
        ]);

        $this->patch('/api/tasks/move-subtasks', [
            'parent_id'=>$parentTask->id,
            'children_ids'=>$childrenTasks->pluck('id')->toArray(),
            'api_token'=>$user->api_token,
        ])
            ->assertJson([
                'id'=>$parentTask->id,
                'children'=>$childrenTasks->toArray()
            ]);

        foreach ($childrenTasks as $childrenTask) {
            $this->assertDatabaseHas('tasks', [
                'id'=>$childrenTask->id,
                'parent_id'=>$parentTask->id
            ]);
        }
    }
}
