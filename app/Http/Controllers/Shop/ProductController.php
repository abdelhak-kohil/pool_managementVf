<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\ProductImage;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(10);
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, \App\Modules\Shop\Actions\Inventory\CreateProductAction $action)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'alert_threshold' => 'required|integer|min:0',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $dto = \App\Modules\Shop\DTOs\ProductData::fromRequest($request);
            $product = $action->execute($dto);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0 
                    ]);
                    
                    if ($index === 0) {
                        $product->update(['image_path' => $path]);
                    }
                }
            }

            return redirect()->route('products.index')->with('success', 'Produit créé avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur création produit : ' . $e->getMessage())->withInput();
        }
    }

    // ... show ...

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product, \App\Modules\Shop\Actions\Inventory\UpdateProductAction $action)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'alert_threshold' => 'required|integer|min:0',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $dto = \App\Modules\Shop\DTOs\ProductData::fromRequest($request);
            $action->execute($product, $dto);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => false
                    ]);
                }
                
                if (!$product->image_path && $product->images()->exists()) {
                    $product->update(['image_path' => $product->images()->first()->image_path]);
                }
            }

            return redirect()->route('products.index')->with('success', 'Produit mis à jour avec succès.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur maj produit : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produit supprimé avec succès.');
    }

    public function deleteImage(ProductImage $image)
    {
        // Delete file from storage
        // Storage::disk('public')->delete($image->image_path); // Optional: delete file
        
        $product = $image->product;
        $image->delete();

        // Update legacy image_path if we deleted the primary image
        if ($product->image_path === $image->image_path) {
            $nextImage = $product->images()->first();
            $product->update(['image_path' => $nextImage ? $nextImage->image_path : null]);
        }

        return back()->with('success', 'Image supprimée.');
    }
}
