<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\Sale;
use App\Models\Member\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ShopController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $products = Product::where('stock_quantity', '>', 0)->with('images')->get();
        
        return view('shop.index', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,other',
            'member_id' => 'nullable|exists:members,member_id',
        ]);
        Log::error($request->all());
        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $saleItems = [];

            foreach ($request->cart as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour {$product->name}");
                }

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $product->decrement('stock_quantity', $item['quantity']);

                $saleItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            $sale = Sale::create([
                'staff_id' => Auth::id(),
                'member_id' => $request->member_id,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
            ]);

            foreach ($saleItems as $item) {
                $sale->items()->create($item);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Vente enregistrée avec succès!', 'sale_id' => $sale->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function searchMembers(Request $request)
    {
        $query = $request->get('q');
        
        $members = Member::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get(['member_id', 'first_name', 'last_name', 'email', 'phone_number', 'photo_path']);

        return response()->json($members);
    }
    public function downloadReceipt($id)
    {
        $sale = Sale::with(['items.product', 'member', 'staff'])->findOrFail($id);

        $items = $sale->items->map(function($item) {
            return [
                'name' => $item->product->name ?? 'Produit supprimé',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->subtotal,
            ];
        });
        
        $data = [
            'receipt_number' => 'SALE-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
            'date' => $sale->created_at->format('d/m/Y H:i'),
            'customer_name' => $sale->member ? ($sale->member->first_name . ' ' . $sale->member->last_name) : 'Client de passage',
            'customer_email' => $sale->member->email ?? '',
            'customer_address' => $sale->member->address ?? '',
            'payment_method' => $sale->payment_method,
            'staff_name' => $sale->staff ? ($sale->staff->first_name . ' ' . $sale->staff->last_name) : 'Système',
            'items' => $items,
            'total' => $sale->total_amount,
        ];

        if (ob_get_length()) ob_end_clean(); // Clear buffer to prevent corrupt PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.receipt', $data);
        return $pdf->download('recu_vente_' . $sale->id . '.pdf');
    }

    public function downloadTicket($id)
    {
        $sale = Sale::with(['items.product', 'member', 'staff'])->findOrFail($id);

        $items = $sale->items->map(function($item) {
            return [
                'name' => $item->product->name ?? 'Produit supprimé',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->subtotal,
            ];
        });

        $data = [
            'receipt_number' => 'TCK-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
            'date' => $sale->created_at->format('d/m/Y H:i'),
            'customer_name' => $sale->member ? ($sale->member->first_name . ' ' . $sale->member->last_name) : 'Client de passage',
            'staff_name' => $sale->staff ? ($sale->staff->first_name . ' ' . $sale->staff->last_name) : 'Système',
            'items' => $items,
            'total' => $sale->total_amount,
        ];

        if (ob_get_length()) ob_end_clean();
        
        // 80mm width = approx 226.77 pt. Height set to long to avoid page breaks.
        $customPaper = array(0, 0, 226.77, 1000);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.ticket', $data)->setPaper($customPaper);
        
        return $pdf->download('ticket_vente_' . $sale->id . '.pdf');
    }

}
