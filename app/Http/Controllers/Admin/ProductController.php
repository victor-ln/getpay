<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Mostra a lista de produtos da conta selecionada.
     */
    public function index(Request $request)
    {
        $selectedAccount = $this->getSelectedAccount($request);

        // Busca os produtos paginados que pertencem À conta selecionada
        $products = Product::where('account_id', $selectedAccount->id)
            ->latest()
            ->paginate(20);

        return view('admin.products.index', compact('products', 'selectedAccount'));
    }

    /**
     * Mostra o formulário para criar um novo produto.
     */
    public function create(Request $request)
    {
        $selectedAccount = $this->getSelectedAccount($request);
        return view('admin.products.create', compact('selectedAccount'));
    }

    /**
     * Guarda um novo produto na base de dados.
     */
    public function store(Request $request)
    {
        $selectedAccount = $this->getSelectedAccount($request);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'status' => 'required|in:active,inactive',
        ]);

        // Adiciona o account_id aos dados validados
        $validatedData['account_id'] = $selectedAccount->id;

        Product::create($validatedData);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Mostra o formulário para editar um produto existente.
     */
    public function edit(Product $product)
    {
        // Autorização: Garante que o utilizador só pode editar produtos da sua conta
        $this->authorize('update', $product);

        return view('admin.products.edit', compact('product'));
    }

    /**
     * Atualiza um produto existente na base de dados.
     */
    public function update(Request $request, Product $product)
    {
        // Autorização
        $this->authorize('update', $product);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'status' => 'required|in:active,inactive',
        ]);

        $product->update($validatedData);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove um produto da base de dados.
     */
    public function destroy(Product $product)
    {
        // Autorização
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

    /**
     * Método auxiliar privado para obter a conta selecionada (lógica do seu Dashboard)
     */
    private function getSelectedAccount(Request $request)
    {
        $user = Auth::user();
        $selectedAccount = null;

        if ($user->isAdmin()) {
            $selectedAccountId = $request->input('account_id', session('selected_account_id'));
            $selectedAccount = Account::find($selectedAccountId);
            if (!$selectedAccount) {
                $selectedAccount = Account::first();
            }
        } else {
            $selectedAccount = $user->accounts()->first();
        }

        if (!$selectedAccount) {
            abort(404, 'No account selected or available.');
        }

        session(['selected_account_id' => $selectedAccount->id]);
        return $selectedAccount;
    }
}
