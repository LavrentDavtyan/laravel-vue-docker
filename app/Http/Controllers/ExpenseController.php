<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query()->where('user_id', $request->user()->id);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        return response()->json($query->orderByDesc('date')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'category'    => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date'        => 'required|date',
        ]);

        $expense = Expense::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return response()->json($expense, 201);
    }

    public function show($id)
    {
        $expense = Expense::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'amount'      => 'sometimes|numeric|min:0.01',
            'category'    => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date'        => 'sometimes|date',
        ]);

        $expense->update($validated);
        return response()->json($expense);
    }

    public function destroy($id)
    {
        $expense = Expense::where('user_id', Auth::id())->findOrFail($id);
        $expense->delete();
        return response()->json(['message' => 'Expense deleted']);
    }
}
