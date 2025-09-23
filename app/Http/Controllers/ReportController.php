<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Mostra a lista de relatórios gerados pelo utilizador.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Pega o ID da conta que está a ser visualizada na sessão
        $selectedAccountId = session('selected_account_id');

        if (!$selectedAccountId) {
            // Redireciona ou mostra uma mensagem se nenhuma conta estiver selecionada
            return redirect()->route('dashboard')->with('error', 'Please select an account to view reports.');
        }

        // Inicia a query base para os relatórios da conta selecionada
        $query = Report::query()->where('account_id', $selectedAccountId);

        // ✅ [A CORREÇÃO] Se o utilizador NÃO for admin, adiciona um filtro
        // para mostrar apenas os relatórios que ele mesmo criou.
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // O resto da lógica continua igual: ordena e pagina o resultado
        $reports = $query->latest()->paginate(15);

        return view('downloads.index', [
            'reports' => $reports
        ]);
    }

    /**
     * Inicia o download de um relatório específico.
     */
    public function download(Report $report)
    {
        // 1. Verificação de Segurança: Garante que o utilizador só pode descarregar os seus próprios relatórios.
        if (Auth::id() !== $report->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // 2. Verificação de Status: Garante que o ficheiro só pode ser descarregado se estiver concluído.
        if ($report->status !== 'completed') {
            return back()->with('error', 'This report is not yet ready for download.');
        }

        // 3. Verificação de Existência: Confirma que o ficheiro realmente existe no disco.
        if (!Storage::disk('local')->exists($report->file_path)) {
            return back()->with('error', 'The report file could not be found. Please try generating it again.');
        }

        // 4. Inicia o Download Seguro
        return Storage::disk('local')->download($report->file_path, $report->file_name);
    }
}
