<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Auth;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Income::where('user_id', Auth::id());

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'category'    => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date'        => 'required|date',
        ]);

        $income = Income::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return response()->json($income, 201);
    }

    public function show($id)
    {
        $income = Income::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($income);
    }

    public function update(Request $request, $id)
    {
        $income = Income::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'amount'      => 'sometimes|numeric|min:0.01',
            'category'    => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date'        => 'sometimes|date',
        ]);

        $income->update($validated);
        return response()->json($income);
    }

    public function destroy($id)
    {
        $income = Income::where('user_id', Auth::id())->findOrFail($id);
        $income->delete();
        return response()->json(['message' => 'Income deleted']);
    }
}
