<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ToastTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAEngine; // Alias para evitar conflito se usar Facade
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Helpers\FormatHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Account;

class UserController extends Controller
{
    // 
    use ToastTrait;

    public function index()
    {


        if (Auth::user()->level == 'admin') {
            $users = User::with('accounts')->latest()->paginate(15);
        } else {
            $users = User::where('id', Auth::user()->id)->paginate(15);
        }



        return view('users.index', compact('users'));
    }

    public function create()
    {
        $user = null;
        $accounts = Account::orderBy('name')->get();
        return view('users.edit', compact('user', 'accounts'));
    }

    public function store(Request $request)
    {

        if (Auth::user()->level == 'client') {
            return $this->updatedSuccess('You are not allowed to create a user!', 'users');
        }





        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6',
            'status' => 'required|in:0,1',
            'level' => 'required|in:admin,client,partner',
        ]);


        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'role' => $validatedData['level'] ?? 'client',
            'level' => $validatedData['level'] ?? 'client',
            'status' => $request->status ?? 0,
        ]);

        if ($validatedData['level'] == 'client' && !empty($request->accounts)) {
            $account = Account::find($request->accounts);
            $account->users()->attach($user->id, ['role' => 'owner']);
        }

        return $this->updatedSuccess('User created successfully!', 'users');
    }

    public function show($id) {}

    public function edit(User $user)
    {
        $user->load('accounts');
        $authenticatedUser = Auth::user();




        if ($authenticatedUser->level !== 'admin' && $authenticatedUser->id !== $user->id)
            return redirect()->route('users')->with('error', 'You can only edit your own profile.');


        if ($authenticatedUser->level == 'admin') {
            // Admin pode ver todas as contas
            $accounts = Account::orderBy('name')->get();
        } else {
            // Cliente só pode ver suas próprias contas vinculadas
            $accounts = $user->accounts()->orderBy('name')->get();
        }

        $qrCodeImage = null;
        $secretKeyForManualEntry = null;


        if ($authenticatedUser->id === $user->id && !empty(session('2fa_unconfirmed_secret_for_user_' . $user->id))) {

            $sessionKeyForUnconfirmedSecret = '2fa_unconfirmed_secret_for_user_' . $authenticatedUser->id;
            $unconfirmedSecretEncrypted = session($sessionKeyForUnconfirmedSecret);



            if ($unconfirmedSecretEncrypted && !$user->two_factor_enabled) {

                try {

                    $secretKeyForManualEntry = Crypt::decryptString($unconfirmedSecretEncrypted);

                    $google2fa = new Google2FAEngine();
                    $qrCodeUrl = $google2fa->getQRCodeUrl(
                        config('app.name', 'GetPay'),
                        $authenticatedUser->email,
                        $secretKeyForManualEntry
                    );



                    $writer = new Writer(
                        new ImageRenderer(
                            new RendererStyle(250), // Tamanho do QR Code
                            new SvgImageBackEnd()
                        )
                    );

                    $qrCodeImage = $writer->writeString($qrCodeUrl); // String SVG do QR Code



                } catch (\Exception $e) {
                    echo ("Erro ao gerar QR Code 2FA para o usuário {$authenticatedUser->id}: " . $e->getMessage());
                    session()->forget($sessionKeyForUnconfirmedSecret); // Limpa sessão se deu erro

                    // $qrCodeImage e $secretKeyForManualEntry permanecem null.
                    // Você pode querer adicionar uma mensagem de erro para o usuário:
                    // session()->flash('error_2fa_setup', 'Could not prepare 2FA setup. Please try enabling 2FA again.');
                }
            }
        }




        return view('users.edit', [
            'user' => $user,
            'accounts' => $accounts,
            'qrCodeImage' => $qrCodeImage,
            'secretKeyForManualEntry' => $secretKeyForManualEntry,
        ]);
    }

    public function enableTwoFactorAuthSetup(Request $request) // Renomeado para clareza se estiver no UserController
    {
        $user = $request->user(); // Sempre o usuário autenticado

        if ($user->two_factor_enabled) {
            return redirect()->route('users.edit', $user->id) // Redireciona de volta para a página de edição do perfil
                ->with('info', '2FA is already enabled.');
        }

        $google2fa = new Google2FAEngine();
        $secret = $google2fa->generateSecretKey();
        session(['2fa_unconfirmed_secret_for_user_' . $user->id => Crypt::encryptString($secret)]);



        return redirect()->route('users.edit', $user->id); // Redireciona para a página de edição que mostrará o QR Code
    }

    public function updatePassword(Request $request, User $user)
    {
        // Garante que apenas o próprio usuário ou um admin possa mudar a senha
        $this->authorize('update', $user);

        // Validação robusta no backend
        $request->validate([
            'password' => [
                'required',
                'confirmed', // Garante que password_confirmation coincide
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        // Atualiza a senha
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully!']);
    }

    public function update(Request $request, $id)
    {





        if (!$request->level) {
            $request['level'] = 'client';
        }

        if (!$request->status) {
            $request['status'] = 1;
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|min:6|confirmed',
            'status' => 'required|in:0,1',
            'document' => 'nullable|string|min:11|max:14',
            'level' => 'required|in:admin,client,partner',
        ]);

        $user = User::find($id);
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->document = $validatedData['document'];
        if (Auth::user()->level == 'admin') {
            $user->status = $validatedData['status'];
            $user->level = $validatedData['level'];

            if (!empty($request->accounts)) {
                $account = Account::find($request->accounts);
                $account->users()->attach($user->id, ['role' => 'owner']);
            }
        }

        $user->save();


        return $this->updatedSuccess('User updated successfully!', 'users');
    }


    public function destroy($id)
    {
        $user = User::find($id);
        $user->feesUser()->delete();
        $user->delete();
        return $this->updatedSuccess('User deleted successfully!', 'users');
    }

    public function updateDocument(Request $request)
    {
        $user = Auth::user();

        // Validação - ajuste as regras conforme a necessidade (CPF, CNPJ, etc.)
        $validated = $request->validate([
            'document' => 'required|string|min:11|max:14', // Exemplo de regra
        ]);

        $user->document = $validated['document'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully!',
            'user' => $user->fresh() // Retorna o objeto do usuário atualizado
        ]);
    }
}
