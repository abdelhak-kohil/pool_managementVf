<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('creator')->orderByDesc('expense_date');

        // Filters
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('reference', 'ilike', "%{$search}%");
            });
        }

        $expenses = $query->paginate(15);

        // Summary for cards
        $totalExpenses = $query->sum('amount'); 

        $categories = [
            'salary' => 'Salaires',
            'electricity' => 'Électricité (Eau + Chauffage)',
            'pool_products' => 'Produits Piscine',
            'equipment' => 'Matériel',
            'maintenance' => 'Maintenance',
            'ads' => 'Publicité',
            'other' => 'Autre'
        ];

        return view('finance.expenses.index', compact('expenses', 'totalExpenses', 'categories'));
    }

    public function store(Request $request, \App\Modules\Finance\Actions\Expenses\CreateExpenseAction $action)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category' => 'required|string',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $dto = \App\Modules\Finance\DTOs\ExpenseData::fromRequest($request);
            $action->execute($dto, Auth::user()->staff_id);
            return redirect()->route('expenses.index')->with('success', 'Dépense ajoutée avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de l\'ajout : ' . $e->getMessage());
        }
    }

    public function update(Request $request, Expense $expense, \App\Modules\Finance\Actions\Expenses\UpdateExpenseAction $action)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category' => 'required|string',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $dto = \App\Modules\Finance\DTOs\ExpenseData::fromRequest($request);
            $action->execute($expense, $dto);
            return redirect()->route('expenses.index')->with('success', 'Dépense mise à jour avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Dépense supprimée.');
    }

    public function exportPdf(Request $request)
    {
        $query = Expense::with('creator')->orderByDesc('expense_date');

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }

        $expenses = $query->get();
        $total = $expenses->sum('amount');

        $pdf = Pdf::loadView('finance.expenses.pdf', compact('expenses', 'total'));
        return $pdf->download('rapport_depenses.pdf');
    }
}
