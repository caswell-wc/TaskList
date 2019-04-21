<?php

namespace App;

use App\Exceptions\TaskCompletionException;
use App\Exceptions\UserMismatchException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class Task
 * @package App
 */
class Task extends Model
{

    /** @var array Fields that will automatically be converted to Carbon instances */
    protected $dates = ['completed_at'];

    /**
     * Define the relationship to the user that the task belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship to the parent task of this task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Define the relationship to the sub-tasks for this task
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /**
     * Create Many tasks at one time.
     *
     * @param $tasks
     * @param User $user
     * @return mixed
     * @throws \Exception
     */
    public static function createMany($tasks, User $user)
    {
        DB::beginTransaction();
        try {
            $startId = DB::table('tasks')->max('id');
            $insertTasks = [];
            foreach ($tasks as $task) {
                $insertTask = [
                    'name' => $task['name'],
                    'parent_id' => null,
                    'user_id' => $user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
                if (!empty($task['parent_id'])) {
                    $parent = Task::find($task['parent_id']);
                    if ((int)$parent->user_id !== (int)$user->id) {
                        throw new UserMismatchException('The parent must have the same user as its children');
                    }
                    $insertTask['parent_id'] = $parent->id;
                }
                $insertTasks[] = $insertTask;
            }

            DB::table('tasks')->insert($insertTasks);

            $insertIds = [];
            for ($i = 1; $i <= count($insertTasks); $i++) {
                array_push($insertIds, $startId + $i);
            }

            $newTasks = Task::whereIn('id', $insertIds)->get();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        DB::commit();

        return $newTasks;

    }

    /**
     * Toggle the completion of an item. If the task is being completed and the task has sub-tasks that are not complete
     * it will throw an exception.
     *
     * @throws TaskCompletionException
     */
    public function toggleComplete()
    {
        if (empty($this->completed_at)) {
            foreach ($this->children as $child) {
                if (empty($child->completed_at)) {
                    throw new TaskCompletionException('All children tasks must be completed before the parent task can be completed.');
                }
            }
            $this->completed_at = Carbon::now();
        } else {
            $this->completed_at = null;
        }
    }

    /**
     * Add a parent task to this task or make this a sub-task
     *
     * @param $parentId
     * @throws UserMismatchException
     */
    public function addParent($parentId)
    {
        $parent = Task::find($parentId);
        if ((int) $parent->user_id !== (int) $this->user_id) {
            throw new UserMismatchException('Parent and Child Tasks must have the same user');
        }
        $this->parent_id = $parentId;
    }

    /**
     * Mass assigns sub-tasks to this Task
     *
     * @param $task_ids
     * @throws UserMismatchException
     */
    public function addSubTasks($task_ids)
    {
        $subTasks = Task::whereIn('id', $task_ids)->get();

        $badTasks = $subTasks->filter(function($subTask, $key) {
            return (int) $subTask->user_id !== (int) $this->user_id;
        });

        if ($badTasks->isNotEmpty()) {
            throw new UserMismatchException('All sub-tasks must have the same user as the parent task.');
        }

        DB::table('tasks')
            ->whereIn('id', $task_ids)
            ->update(['parent_id'=>$this->id]);
    }
}
