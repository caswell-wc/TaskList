<?php

namespace Tests\Unit;

use App\Exceptions\TaskCompletionException;
use App\Exceptions\UserMismatchException;
use App\Task;
use Carbon\Carbon;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     */
    public function createMany()
    {
        $user = factory(User::class)->create();
        $taskData = [
            [
                'name'=>'Task 1',
                'parent_id'=>null
            ],
            [
                'name'=>'Task 2',
                'parent_id'=>null
            ]
        ];

        $tasks = Task::createMany($taskData, $user);

        $taskNames = $tasks->pluck('name')->toArray();
        $this->assertTrue(in_array('Task 1', $taskNames));
        $this->assertTrue(in_array('Task 2', $taskNames));

        foreach ($taskData as $task) {
            $this->assertDatabaseHas('tasks', [
                'name'=>$task['name'],
                'parent_id'=>null
            ]);
        }
    }

    /**
     * @test
     */
    public function createManyFailsWhenGivenABadParent()
    {
        $this->expectException(UserMismatchException::class);

        $user = factory(User::class)->create();
        $parent = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);
        $user2 = factory(User::class)->create();
        $parent2 = factory(Task::class)->create([
            'user_id'=>$user2->id
        ]);
        $taskData = [
            [
                'name'=>'Task 1',
                'parent_id'=>$parent->id
            ],
            [
                'name'=>'Task 2',
                'parent_id'=>$parent2->id
            ]
        ];

        Task::createMany($taskData, $user);

        foreach ($taskData as $task) {
            $this->assertDatabaseMissing('tasks', [
                'name'=>$task['name'],
                'parent_id'=>null
            ]);
        }
    }

    /**
     * @test
     */
    public function aTaskCanBeCompleted()
    {
        $task = factory(Task::class)->make();
        Carbon::setTestNow(Carbon::create(2019, 4, 19, 12));
        $task->toggleComplete();

        $this->assertEquals('2019-04-19 12:00:00', $task->completed_at);
    }

    /**
     * @test
     */
    public function aTaskCanBeUncompleted()
    {
        $task = factory(Task::class)->make([
            'completed_at'=>Carbon::now()
        ]);
        $task->toggleComplete();

        $this->assertNull($task->completed_at);
    }

    /**
     * @test
     */
    public function addAParentTask()
    {
        $user = factory(User::class)->create();
        $parentTask = factory(Task::class)->create([
            'user_id'=>$user->id,
        ]);
        $task = factory(Task::class)->create([
            'user_id'=>$user->id,
        ]);

        $task->addParent($parentTask->id);

        $this->assertEquals($parentTask->id, $task->parent_id);
    }

    /**
     * @test
     */
    public function addAParentThrowsExceptionWhenTheUserIdIsWrong()
    {
        $this->expectException(UserMismatchException::class);

        $parentTask = factory(Task::class)->create([
            'user_id'=>1,
        ]);
        $task = factory(Task::class)->create([
            'user_id'=>2,
        ]);

        $task->addParent($parentTask->id);

        $this->assertNotEquals($parentTask->id, $task->parent_id);
    }

    /**
     * @test
     */
    public function toggleCompleteFailsIfCompletingATaskWithIncompleteChildren()
    {
        $this->expectException(TaskCompletionException::class);

        $parentTask = factory(Task::class)->create([
            'user_id'=>1,
        ]);
        factory(Task::class)->create([
            'user_id'=>1,
            'parent_id'=>$parentTask->id,
        ]);

        $parentTask->toggleComplete();

        $this->assertNull($parentTask->completed_at);
    }

    /**
     * @test
     */
    public function addSubTasks()
    {
        $parentTask = factory(Task::class)->create([
            'user_id'=>1
        ]);

        $childrenTasks = factory(Task::class, 2)->create([
            'user_id'=>1
        ]);

        $parentTask->addSubTasks($childrenTasks->pluck('id')->toArray());

        foreach ($childrenTasks->fresh() as $childrenTask) {
            $this->assertEquals($parentTask->id, $childrenTask->parent_id);
        }
    }

    /**
     * @test
     */
    public function addSubTasksFailsWhenAChildIsNotAssignedToTheParentUser()
    {
        $this->expectException(UserMismatchException::class);

        $parentTask = factory(Task::class)->create([
            'user_id'=>1
        ]);

        $childrenTasks = factory(Task::class, 2)->create([
            'user_id'=>1
        ]);

        $badChild = factory(Task::class)->create([
            'user_id'=>2
        ]);

        $childIds = $childrenTasks->pluck('id')->toArray();
        $childIds[] = $badChild->id;

        $parentTask->addSubTasks($childIds);

        foreach ($childrenTasks->fresh() as $childrenTask) {
            $this->assertNull($childrenTask->parent_id);
        }
    }
}
