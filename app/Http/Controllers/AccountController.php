<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Bank;
use App\Models\Fee;
use App\Models\FeeProfile;
use App\Models\User;
use App\Traits\ToastTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{

    use ToastTrait;
    /**
     * Display a listing of the resource. 
     */
    /** 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();



        if ($user->isAdmin()) {
            $query = Account::with(['partner', 'users', 'acquirer']);
        } else {
            // Adiciona 'acquirer' também para não-admin se necessário
            $query = Account::whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->with(['partner', 'users', 'acquirer']);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('users', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('name', 'LIKE', '%' . $searchTerm . '%')
                            ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('partner', function ($partnerQuery) use ($searchTerm) {
                        $partnerQuery->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })

                    ->orWhereHas('acquirer', function ($acquirerQuery) use ($searchTerm) {
                        $acquirerQuery->where('name', 'LIKE', '%' . $searchTerm . '%');
                    });
            });
        }

        $accounts = $query->latest()->paginate(100);
        $accounts->appends($request->query());

        return view('accounts.index', compact('accounts'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized Access');
        }


        // Busca todos os usuários que são sócios para popular o <select> no formulário
        $availablePartners = User::where('level', User::LEVEL_PARTNER)->orderBy('name')->get();
        $banks = Bank::where('active', 1)->get();



        // Retorna a view do formulário de criação
        return view('accounts.edit', compact('availablePartners', 'banks'));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        // MUDANÇA 1: Ajustar as regras de validação
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'partner_id' => 'nullable|exists:users,id',
            'min_amount_transaction' => 'nullable|numeric|min:0',
            'max_amount_transaction' => 'nullable|numeric|min:0',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255|unique:users,email',
            'user_password' => 'nullable|string|min:8',
            'acquirer_id' => 'nullable|exists:banks,id',
        ]);

        try {
            DB::beginTransaction();

            // Passo 1: Criar o usuário (nenhuma mudança aqui)
            if (!empty($validatedData['user_name']) && !empty($validatedData['user_email']) && !empty($validatedData['user_password'])) {
                $user = User::create([
                    'name' => $validatedData['user_name'],
                    'email' => $validatedData['user_email'],
                    'password' => bcrypt($validatedData['user_password']),
                    'level' => User::LEVEL_CLIENT, // Usando a constante do model
                    'status' => 1
                ]);
            }



            // Passo 2: Criar a conta
            $accountData = [
                'name' => $validatedData['name'],
                'min_amount_transaction' => $validatedData['min_amount_transaction'],
                'max_amount_transaction' => $validatedData['max_amount_transaction'],
                'partner_id' => $validatedData['partner_id'],
                'acquirer_id' => $validatedData['acquirer_id'],
            ];

            // Adiciona o partner_id apenas se ele foi enviado
            if (!empty($validatedData['partner_id'])) {
                $accountData['partner_id'] = $validatedData['partner_id'];
            }

            // MUDANÇA 2: Remover o campo 'user_id' que não existe na tabela accounts
            $account = Account::create($accountData);


            $balance = Balance::create([
                'account_id' => $account->id,
                'acquirer_id' => $account->acquirer_id ?? null,
                'available_balance' => 0,  // ← Correção
                'blocked_balance' => 0
            ]);



            // MUDANÇA 3 (A LIGAÇÃO): Conectar o usuário à conta na tabela 'account_user'
            // O segundo parâmetro do attach() define os valores para as colunas extras da tabela pivot.
            if (isset($user)) {
                $account->users()->attach($user->id, ['role' => 'owner']);
            }

            DB::commit();

            // Você pode redirecionar para a página de edição da conta recém-criada
            return $this->updatedSuccess('Account created successfully!', 'accounts.index');
        } catch (\Exception $e) {
            DB::rollBack();

            // Retornar com o erro para depuração
            $msg = "Error: " . $e->getMessage();
            return $this->updatedErr($msg, 'accounts.index');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account)
    {
        $user = Auth::user();

        // Verificar se é admin OU se a conta pertence ao usuário
        if (!$user->isAdmin() && !$user->accounts()->whereKey($account->id)->exists()) {
            abort(403, 'You do not have permission to access this account.');
        }



        $account->load('users', 'webhooks', 'pixKeys', 'fees', 'profitSharingPartners', 'feeProfiles');

        $availableProfiles = FeeProfile::orderBy('name')->get();

        $existingPartnerIds = $account->profitSharingPartners->pluck('id');


        $availablePartners = User::where('level', User::LEVEL_PARTNER)
            ->whereNotIn('id', $existingPartnerIds)
            ->orderBy('name')
            ->get();


        $partners = User::where('level', User::LEVEL_PARTNER)->orderBy('name')->get();
        $banks = Bank::where('active', 1)->get();




        return view('accounts.edit', compact('account', 'partners', 'availablePartners', 'existingPartnerIds', 'availableProfiles', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {


        if (Auth::user()->level == 'admin') {
            $account->update([
                'name' => $request->name,
                'min_amount_transaction' => $request->min_amount_transaction,
                'max_amount_transaction' => $request->max_amount_transaction,
                'partner_id' => $request->partner_id,
                'acquirer_id' => $request->acquirer_id
            ]);
        } else {
            $account->update([
                'name' => $request->name,
            ]);
        }

        return $this->updatedSuccess('Account updated successfully!', 'accounts.edit', $account->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        //
    }

    public function detachUser(Account $account, User $user)
    {
        // O método detach() remove o registro da tabela pivot 'account_user'
        $account->users()->detach($user->id);

        return back()->with('success', 'Usuário removido da conta com sucesso!');
    }

    public function addUser(Request $request, Account $account)
    {
        // Autoriza se o usuário logado pode modificar esta conta



        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email', // Garante que o email é único no sistema
            'password' => ['required', Password::min(8)],
            'role' => 'required|string|in:owner,admin,member',
        ]);

        // Cria o novo usuário
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'level' => 'client', // Todo usuário criado aqui é do tipo client
            'status' => true
        ]);

        // Vincula o novo usuário à conta com o papel especificado
        $account->users()->attach($newUser->id, ['role' => $validated['role']]);

        // Recarrega o usuário com o relacionamento para obter o 'pivot'
        $userRow = $account->users()->find($newUser->id);

        return response()->json([
            'success' => true,
            'message' => 'User added to account successfully!',
            // Retorna o HTML da linha da tabela já renderizado
            'html' => view('accounts.partials.user-row', ['user' => $userRow, 'account' => $account])->render()
        ]);
    }

    public function attachPartner(Request $request, Account $account)
    {
        $this->authorize('update', $account);

        $data = $request->validate([
            'partner_id' => ['required', 'exists:users,id'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'platform_withdrawal_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        // --- [NOVO] INÍCIO DA VALIDAÇÃO DE 100% ---

        // 1. Pega a comissão que está tentando ser adicionada (em formato decimal)
        $newCommissionRate = $data['commission_rate'] / 100;

        // 2. Soma as comissões que JÁ existem para esta conta diretamente no banco
        // O Eloquent é inteligente e soma a coluna da tabela pivot.
        $currentTotalCommission = $account->profitSharingPartners()->sum('commission_rate');

        // 3. Verifica se o total ultrapassaria 100% (representado por 1.0)
        if (($currentTotalCommission + $newCommissionRate) > 1) {

            return response()->json([
                'success' => false, // Opcional, mas bom manter
                'message' => 'Adding this commission exceeds the 100% limit for this account. Current total: ' . number_format($currentTotalCommission * 100, 2) . '%'
            ], 422);
        }

        // --- FIM DA VALIDAÇÃO DE 100% ---

        // Se a validação passar, o resto do código é executado normalmente.
        $account->profitSharingPartners()->attach($data['partner_id'], [
            'commission_rate' => $newCommissionRate, // Usa o valor já convertido
            'platform_withdrawal_fee_rate' => $data['platform_withdrawal_fee_rate'] / 100
        ]);

        // [MODIFICADO] A resposta para o AJAX continua a mesma
        $partner = $account->profitSharingPartners()->find($data['partner_id']);
        return response()->json([
            'success' => true,
            'message' => 'Partner participation successfully added!',
            'html' => view('accounts.partials.partner-row', ['account' => $account, 'partner' => $partner])->render()
        ]);
    }

    public function detachPartner(Account $account, User $partner)
    {
        $this->authorize('update', $account);
        $account->profitSharingPartners()->detach($partner->id);
        return response()->json([
            'success' => true,
            'message' => 'Partner participation successfully removed!'
        ]);
    }

    // NOVO MÉTODO PARA ANEXAR UM PERFIL
    public function attachFeeProfile(Request $request, Account $account)
    {
        $validated = $request->validate([
            'fee_profile_id' => 'required|exists:fee_profiles,id',
            'transaction_type' => 'required|in:IN,OUT,DEFAULT',
        ]);

        DB::transaction(function () use ($account, $validated) {
            $newType = $validated['transaction_type'];
            $newProfileId = $validated['fee_profile_id'];

            if ($newType === 'DEFAULT') {
                // Desativa TODAS as regras ativas desta conta
                DB::table('account_fee_profile')
                    ->where('account_id', $account->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
            } else {
                // Desativa regras específicas do mesmo tipo
                DB::table('account_fee_profile')
                    ->where('account_id', $account->id)
                    ->where('transaction_type', $newType)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);

                // Desativa regras DEFAULT ativas
                DB::table('account_fee_profile')
                    ->where('account_id', $account->id)
                    ->where('transaction_type', 'DEFAULT')
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
            }

            // Adiciona a nova regra
            $account->feeProfiles()->attach($newProfileId, [
                'transaction_type' => $newType,
                'status' => 'active',
            ]);
        });

        return back()->with('success', 'Fee profile assigned successfully.');
    }


    public function detachFeeProfile(Request $request, Account $account, FeeProfile $feeProfile)
    {
        // Este método agora desativa a regra em vez de deletá-la, para manter o histórico.
        $transactionType = $request->input('transaction_type');

        $account->feeProfiles()
            ->where('fee_profile_id', $feeProfile->id)
            ->where('transaction_type', $transactionType)
            ->where('status', 'active') // Segurança extra: só desativa regras ativas
            ->updateExistingPivot($feeProfile->id, [
                'status' => 'inactive'
            ]);

        return back()->with('success', 'Fee profile has been deactivated.');
    }
}
