<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use App\Models\Bank;
use App\Models\Payment;
use App\Services\Acquirers\DubaiAcquirerService;
use App\Services\Acquirers\LumenAcquirerService;
use App\Services\Acquirers\TruztAcquirerService;
use App\Services\Acquirers\XdpagAcquirerService;
use App\Services\Acquirers\OwenAcquirerService;
use App\Services\Acquirers\E2AcquirerService;
use Exception; // Use a Exception genérica ou crie uma específica, como AcquirerNotFoundException

class AcquirerResolverService
{
    /**
     * Mapeamento de nomes de adquirentes para suas classes de serviço.
     * @var array
     */
    protected $acquirerClasses = [
        'dubai' => DubaiAcquirerService::class,
        'lumenpay' => TruztAcquirerService::class,
        'truztpix' => TruztAcquirerService::class,
        'xdpag' => XdpagAcquirerService::class,
        'owen' => OwenAcquirerService::class,
        'e2' => E2AcquirerService::class,
    ];

    /**
     * Seleciona o adquirente ativo e retorna sua instância de serviço.
     *
     * @param array $data Dados adicionais, se necessários para a seleção (atualmente não usados na seleção).
     * @return object Uma instância do serviço do adquirente (e.g., DubaiAcquirerService).
     * @throws \Exception Se nenhum adquirente ativo for encontrado ou o adquirente não estiver mapeado.
     */
    public function resolveAcquirerService(Account $account): object
    {

        // 1. Encontra o adquirente ativo (lógica atual)
        //$activeBank = Bank::where('active', true)->first();

        $acquirer = $account->acquirer;


        if (!$acquirer) {
            throw new Exception('No active acquirers found in the system.');
        }

        if (!$acquirer->active) {
            throw new Exception('No active acquirers found in the system.');
        }


        // 2. Extrai o nome do adquirente (lógica atual)
        $nameParts = explode(' ', $acquirer->name);
        $acquirerName = strtolower($nameParts[0]);


        // 3. Verifica se o adquirente está mapeado para uma classe
        if (!isset($this->acquirerClasses[$acquirerName])) {
            throw new Exception("Acquirer service for '{$acquirerName}' not configured.");
        }

        // 4. Obtém a classe e instancia o serviço
        $acquirerServiceClass = $this->acquirerClasses[$acquirerName];


        // Passa o objeto Bank para o construtor do serviço do adquirente
        // Certifique-se de que todos os seus serviços de adquirente (DubaiAcquirerService, etc.)
        // aceitem uma instância de App\Models\Bank no seu construtor.


        return new $acquirerServiceClass($acquirer);
    }

    public function resolveByBank(Bank $bank): object
    {
        // 1. Valida se o banco está ativo
        if (!$bank->active) {
            throw new Exception("O adquirente '{$bank->name}' está inativo.");
        }

        // 2. Extrai o nome do adquirente (a sua lógica original)
        $nameParts = explode(' ', $bank->name);
        $acquirerName = strtolower($nameParts[0]);

        // 3. Verifica se o adquirente está mapeado para uma classe de serviço
        if (!isset($this->acquirerClasses[$acquirerName])) {
            throw new Exception("Serviço de adquirente para '{$acquirerName}' não configurado.");
        }

        // 4. Obtém o nome da classe e instancia o serviço, passando o objeto do banco
        $acquirerServiceClass = $this->acquirerClasses[$acquirerName];

        return new $acquirerServiceClass($bank);
    }

    /**
     * 
     *
     * @param Payment $payment O registro do pagamento original.
     * @return object O serviço da adquirente que processou o pagamento.
     * @throws \Exception
     */
    public function resolveFromPayment(Payment $payment): object
    {
        // REVISÃO: Em vez de usar a relação mágica '$payment->provider',
        // usamos o 'provider_id' do pagamento para buscar o banco diretamente.
        $originalAcquirer = Bank::find($payment->provider_id);

        // A verificação agora é mais clara. Se não encontrarmos o banco, lançamos um erro.
        if (!$originalAcquirer) {
            throw new \Exception("O pagamento com ID {$payment->id} tem um provider_id ({$payment->provider_id}), mas nenhum Bank foi encontrado com este ID.");
        }

        // O resto da sua função continua exatamente igual...
        $nameParts = explode(' ', $originalAcquirer->name);
        $acquirerName = strtolower($nameParts[0]);

        if (!isset($this->acquirerClasses[$acquirerName])) {
            throw new \Exception("Serviço da adquirente '{$acquirerName}' não está configurado.");
        }

        $acquirerServiceClass = $this->acquirerClasses[$acquirerName];

        return new $acquirerServiceClass($originalAcquirer);
    }
}
