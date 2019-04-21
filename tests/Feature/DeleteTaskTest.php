<?php

namespace Tests\Feature;

use App\Task;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteTaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function aUserCanDeleteATask()
    {
        $user = factory(User::class)->create();

        $task = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);

        $this->delete('/api/tasks/' . $task->id . '?api_token=' . $user->api_token)
            ->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id'=>$task->id]);
    }

    /**
     * @test
     */
    public function aUserCanNotDeleteAnotherUsersTask()
    {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $task = factory(Task::class)->create([
            'user_id'=>$user->id
        ]);

        $this->delete('/api/tasks/' . $task->id . '?api_token=' . $user2->api_token)
            ->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id'=>$task->id]);
    }
}
