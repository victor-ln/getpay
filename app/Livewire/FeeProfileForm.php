<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\FeeProfile;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FeeProfileForm extends Component
{
    // Propriedades para um perfil existente (modo de edição)
    public ?FeeProfile $profile = null;

    // Propriedades públicas são automaticamente sincronizadas com a view (como o 'state' no React)
    public $name;
    public $description;
    public $calculation_type = 'SIMPLE_FIXED'; // Valor inicial padrão
    public $fixed_fee;
    public $base_fee;
    public $percentage_fee;
    public $account_id = ''; // Inicia como string vazia para o placeholder do select

    // Array para gerenciar as faixas de valor dinâmicas
    public array $tiers = [];

    // 'mount' é como o construtor do Livewire. Ele roda quando o componente é carregado.
    public function mount(FeeProfile $profile = null)
    {
        if ($profile && $profile->exists) {
            $this->profile = $profile;
            $this->name = $profile->name;
            $this->description = $profile->description;
            $this->calculation_type = $profile->calculation_type;
            $this->fixed_fee = $profile->fixed_fee;
            $this->base_fee = $profile->base_fee;
            $this->percentage_fee = $profile->percentage_fee;
            $this->account_id = $profile->account_id;

            // Se o perfil for do tipo TIERED, carrega as faixas existentes
            if ($this->calculation_type === 'TIERED') {
                $this->tiers = $profile->tiers()->get(['min_value', 'max_value', 'fixed_fee', 'percentage_fee'])->toArray();
            }
        }
    }

    public function updated($propertyName)
    {
        // Lista de propriedades que devem ser nulas se estiverem vazias
        $numericNullable = [
            'fixed_fee',
            'base_fee',
            'percentage_fee',
        ];

        if (in_array($propertyName, $numericNullable) && $this->{$propertyName} === '') {
            // Se a propriedade atualizada for uma das nossas taxas e seu valor for
            // uma string vazia, nós a definimos como null.
            $this->{$propertyName} = null;
        }
    }



    // Ação para adicionar uma nova linha de faixa de valor
    public function addTier()
    {
        $this->tiers[] = ['min_value' => '', 'max_value' => '', 'fixed_fee' => '', 'percentage_fee' => ''];
    }

    // Ação para remover uma linha de faixa de valor
    public function removeTier($index)
    {
        unset($this->tiers[$index]);
        $this->tiers = array_values($this->tiers); // Re-indexa o array
    }

    // Ação principal de salvamento, chamada pelo formulário
    public function save()
    {
        try {
            $validatedData = $this->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'calculation_type' => 'required|string|in:SIMPLE_FIXED,GREATER_OF_BASE_PERCENTAGE,TIERED',
                'account_id' => 'nullable|exists:accounts,id',
                'fixed_fee' => 'required_if:calculation_type,SIMPLE_FIXED|nullable|numeric',
                'base_fee' => 'required_if:calculation_type,GREATER_OF_BASE_PERCENTAGE|nullable|numeric',
                'percentage_fee' => 'required_if:calculation_type,GREATER_OF_BASE_PERCENTAGE|nullable|numeric',
                'tiers' => $this->calculation_type === 'TIERED' ? 'required|array|min:1' : 'nullable|array',
                'tiers.*.min_value' => 'required|numeric',
                'tiers.*.max_value' => 'nullable|numeric|gt:tiers.*.min_value',
                'tiers.*.fixed_fee' => 'nullable|numeric',
                'tiers.*.percentage_fee' => 'nullable|numeric',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', $e->errors());
            session()->flash('error', 'Erro de validação: ' . collect($e->errors())->flatten()->first());
            return;
        }



        try {
            DB::beginTransaction();

            $profileData = [
                'name' => $this->name,
                'description' => $this->description,
                'calculation_type' => $this->calculation_type,
                'fixed_fee' => $this->calculation_type === 'SIMPLE_FIXED' ? $this->fixed_fee : null,
                'base_fee' => $this->calculation_type === 'GREATER_OF_BASE_PERCENTAGE' ? $this->base_fee : null,
                'percentage_fee' => $this->calculation_type === 'GREATER_OF_BASE_PERCENTAGE' ? $this->percentage_fee : null,
                'account_id' => $this->account_id ?: null,
            ];



            // Se estamos editando, atualiza. Se não, cria.
            $profile = FeeProfile::updateOrCreate(['id' => $this->profile?->id], $profileData);

            $cleanTiers = collect($this->tiers)->map(function ($tier) {
                // Para cada campo que pode ser nulo, se for uma string vazia, converte para null.
                $tier['max_value'] = $tier['max_value'] === '' ? null : $tier['max_value'];
                $tier['fixed_fee'] = $tier['fixed_fee'] === '' ? null : $tier['fixed_fee'];
                $tier['percentage_fee'] = $tier['percentage_fee'] === '' ? null : $tier['percentage_fee'];

                return $tier;
            })->all();

            if ($this->calculation_type === 'TIERED') {
                // A lógica de limpeza e criação agora só acontece se o tipo for TIERED.
                $cleanTiers = collect($this->tiers)->map(function ($tier) {
                    $tier['max_value'] = $tier['max_value'] === '' ? null : $tier['max_value'];
                    $tier['fixed_fee'] = $tier['fixed_fee'] === '' ? null : $tier['fixed_fee'];
                    $tier['percentage_fee'] = $tier['percentage_fee'] === '' ? null : $tier['percentage_fee'];
                    return $tier;
                })->all();

                // Sincroniza as faixas: apaga as antigas e cria as novas.
                $profile->tiers()->delete();
                $profile->tiers()->createMany($cleanTiers);
            } else {
                // CORREÇÃO: Se o perfil NÃO for do tipo TIERED, garante que quaisquer 
                // faixas antigas associadas a ele sejam removidas.
                $profile->tiers()->delete();
            }

            DB::commit();

            session()->flash('success', 'Fee Profile saved successfully.');
            return redirect()->route('fee-profiles.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error saving fee profile: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Passa a lista de contas para a view do componente
        $accounts = Account::all(['id', 'name']);
        return view('livewire.fee-profile-form', compact('accounts'));
    }
}
