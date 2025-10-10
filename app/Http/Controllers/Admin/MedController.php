<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Services\AcquirerResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedController extends Controller
{
    protected $acquirerResolver;

    public function __construct(AcquirerResolverService $acquirerResolver)
    {
        $this->acquirerResolver = $acquirerResolver;
    }

    /**
     * Exibe a página principal de gestão de MEDs.
     */
    public function index()
    {
        // Apenas para garantir, mas o middleware de rota já deve proteger
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Busca todos os bancos ativos para criar as abas
        $banks = Bank::where('active', true)->orderBy('name')->get();

        // Para uma performance inicial rápida, carregamos os dados apenas do primeiro banco
        $initialMedData = [];
        if ($banks->isNotEmpty()) {
            $firstBank = $banks->first();
            $initialMedData = $this->fetchMedData($firstBank);
        }

        return view('admin.meds.index', [
            'banks' => $banks,
            'initialMedData' => $initialMedData,
            'initialBankId' => $banks->first()->id ?? null,
        ]);
    }

    /**
     * Busca e devolve os dados de MED para um banco específico (usado por AJAX).
     */
    public function getMedDataForBank(Bank $bank, Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $page = $request->input('page', 1);
        $medData = $this->fetchMedData($bank, $page);

        // Renderiza a tabela passando as variáveis corretamente
        $html = view('_partials.meds-table', [
            'med' => $medData['med'],
            'total' => $medData['total'],
            'pagination' => $medData['pagination']
        ])->render();

        return response()->json(['html' => $html]);
    }

    private function fetchMedData(Bank $bank, int $page = 1)
    {
        try {
            $acquirerService = $this->acquirerResolver->resolveByBank($bank);

            if (method_exists($acquirerService, 'getMeds')) {
                $token = $acquirerService->getToken();
                if (!$token) {
                    \Log::warning('Token não obtido para o banco: ' . $bank->name);
                    return [
                        'statusCode' => 401,
                        'med' => [],
                        'total' => 0,
                        'pagination' => [
                            'count' => 0,
                            'page' => 1,
                            'perPage' => 20
                        ]
                    ];
                }

                $dateFrom = now()->subDays(7)->toDateString();
                $dateTo = now()->toDateString();

                $filters = [
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'page' => $page,
                    'perPage' => 20, // Adicione isso
                ];

                $response = $acquirerService->getMeds($filters, $token);

                $apiData = $response['data'] ?? [];
                $data = $apiData['med'] ?? [];

                $pagination = $apiData['pagination'] ?? [
                    'count' => count($data),
                    'page' => $page,
                    'perPage' => 20
                ];

                $total = $apiData['total'] ?? count($data);

                if (!is_array($data)) {
                    $data = [];
                }

                return [
                    'statusCode' => $response['statusCode'] ?? 200,
                    'med' => $data,
                    'total' => $total,
                    'pagination' => $pagination
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar MEDs:', [
                'banco' => $bank->name ?? 'desconhecido',
                'error' => $e->getMessage()
            ]);
            report($e);
        }

        return [
            'statusCode' => 500,
            'med' => [],
            'total' => 0,
            'pagination' => [
                'count' => 0,
                'page' => $page,
                'perPage' => 20
            ]
        ];
    }
}
