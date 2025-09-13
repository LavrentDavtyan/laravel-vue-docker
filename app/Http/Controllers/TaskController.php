<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class TaskController extends Controller
{
    public function index() {
        return Task::all();
    }

    public function store(Request $request) {
        $task = Task::create([
            'title' => $request->title,
            'completed' => false
        ]);
        return response()->json($task);
    }

    public function update(Request $request, Task $task) {
        $task->completed = $request->completed;
        $task->save();
        return response()->json($task);
    }

    public function destroy(Task $task) {
        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }

}
