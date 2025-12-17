<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->paginate(10);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request, \App\Modules\Shop\Actions\Inventory\CreateCategoryAction $action)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,equipment',
        ]);

        try {
            $dto = \App\Modules\Shop\DTOs\CategoryData::fromRequest($request);
            $action->execute($dto);
            return redirect()->route('categories.index')->with('success', 'Catégorie créée avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur création catégorie : ' . $e->getMessage());
        }
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category, \App\Modules\Shop\Actions\Inventory\UpdateCategoryAction $action)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,equipment',
        ]);

        try {
            $dto = \App\Modules\Shop\DTOs\CategoryData::fromRequest($request);
            $action->execute($category, $dto);
            return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur maj catégorie : ' . $e->getMessage());
        }
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée avec succès.');
    }
}
