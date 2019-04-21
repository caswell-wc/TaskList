<?php

namespace App\Http\Controllers;

use App\Exceptions\TaskCompletionException;
use App\Exceptions\UserMismatchException;
use App\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Class TaskController
 * @package App\Http\Controllers
 */
class TaskController extends Controller
{

    /**
     * Get a list of all the tasks for the authenticated user
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        return Task::where('user_id', $request->user()->id)->get();
    }

    /**
     * Get a single Task
     *
     * @param Request $request
     * @param Task $task
     * @return Task|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function show(Request $request, Task $task)
    {
        if ($request->user()->id !== (int) $task->user_id) {
            return response('Unauthorized', 403)
                ->header('Content-Type', 'text/plain');
        }
        $task->load('children');
        return $task;
    }

    /**
     * Create a new task
     *
     * @param Request $request
     * @return Task
     * @throws \Exception
     */
    public function store(Request $request)
    {
        try {
            if (isset($request->tasks)) {
                /**
                 * The createMany function is designed to make it so you can insert all tasks in one call to the database
                 * and still be able to return the tasks that were created. For a system like this where the user is probably
                 * not going to want to add a large number of tasks at once it would probably be better to just loop over
                 * the tasks and do an insert for each one so you can avoid the overhead but I wanted to show how I would
                 * handle this if large inserts were necessary.
                 */
                $tasks = Task::createMany($request->tasks, $request->user());
            } else {
                $task = new Task();
                $task->name = $request->name;
                $task->user()->associate($request->user());
                if (!empty($request->parent_id)) {
                    $task->addParent($request->parent_id);
                }
                $task->save();

                $tasks = new Collection();
                $tasks->push($task);
            }
        } catch (UserMismatchException $e) {
            return Response($e->getMessage(), 403);
        }

        return $tasks;
    }

    /**
     * Update the name on a task
     *
     * @param Request $request
     * @param Task $task
     * @return Task|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        if ($request->user()->id !== (int) $task->user_id) {
            return response('Unauthorized', 403)
                ->header('Content-Type', 'text/plain');
        }

        $task->name = $request->name;
        $task->save();

        return $task;
    }

    /**
     * Delete a task
     *
     * @param Request $request
     * @param Task $task
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function delete(Request $request, Task $task)
    {
        if ($request->user()->id !== (int) $task->user_id) {
            return response('Unauthorized', 403)
                ->header('Content-Type', 'text/plain');
        }

        $task->delete();

        return Response('', 204);
    }

    /**
     * Toggle the completion of the given task.
     *
     * @param Request $request
     * @param Task $task
     * @return Task|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function toggleComplete(Request $request, Task $task)
    {
        if ($request->user()->id !== (int) $task->user_id) {
            return response('Unauthorized', 403)
                ->header('Content-Type', 'text/plain');
        }

        try {
            $task->toggleComplete();
            $task->save();
        } catch(TaskCompletionException $e) {
            return Response($e->getMessage(), 409);
        }

        return $task;
    }

    /**
     * Move one or more tasks to be sub-tasks of the given parent task
     *
     * @param Request $request
     * @return mixed
     */
    public function moveSubTasks(Request $request)
    {
        $parentTask = Task::find($request->parent_id);

        $parentTask->addSubTasks($request->children_ids);

        return $parentTask->load('children');
    }
}
