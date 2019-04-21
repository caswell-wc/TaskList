# TaskList

This is a basic Task List api that can be used for managing the backend of a task list. It allows you to do the following:
1. Get a list of tasks for a single user
    1. Send a get request to <your_domain>/api/tasks?api_token=<api_token>
2. View a single task and any sub-tasks assigned to it
    1. Send a get request to <your_domain>/api/tasks/<task_id>?api_token=<api_token>
3. Create one or more tasks in a single api call
    1. Send a post request to <your_domain>/api/tasks
4. Create sub-tasks to be able to break down larger tasks into small pieces
    1. Send a post request to <your_domain>/api/tasks
5. Move multiple tasks to be sub-tasks of a single parent task
    1. Send a patch request to <your_domain>/api/tasks/move-subtasks
6. Update the name of a task
    1. Send a patch request to <your_domain>/api/tasks/<task_id>
7. Complete Tasks
    1. Send a patch request to <your_domain>/api/tasks/<task_id>/toggle-complete?api_token=<api_token>
    2. Completing Parent tasks will only work when all sub-tasks have been completed
    3. You can also remove the completion of an item using this same endpoint
8. Delete a task
    1. Send a delete request to <your_domain>/api/tasks/<task_id>?api_token=<api_token>

## User Creation
To create a user, you can go to <your_domain>/register and create a new user. 
Once this user is created you can login at <your_domain>/login and see the token that was generated for the user.

## API Json Schemas
In the documentation folder of this repo, you will find schemas for all endpoints and their responses to help you in
creating your messages. There is also a Postman export to show you some examples. For the Postman requests to work, you
will have to update the domain and api_token to match your configuration.
