<div>
    {{-- O formulário agora chama o método 'save' do Livewire --}}
    <form wire:submit.prevent="save">
        @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="form-group mb-3">
            <label for="name">Profile Name</label>
            <input type="text" id="name" class="form-control" wire:model="name">
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>


        <div class="form-group mb-3">
            <label for="calculation_type">Calculation Type</label>
            {{-- wire:model.live faz a UI re-renderizar instantaneamente ao mudar --}}
            <select id="calculation_type" class="form-control" wire:model.live="calculation_type">
                <option value="SIMPLE_FIXED">Simple Fixed </option>
                <option value="GREATER_OF_BASE_PERCENTAGE">Greater of Base and % </option>
                <option value="TIERED">Tiered by Value </option>
            </select>
        </div>

        {{-- Lógica condicional com Blade. Muito mais limpo que JS! --}}
        @if ($calculation_type === 'SIMPLE_FIXED')
        <div class="card p-3 mb-3">
            <h5>Simple Fixed Fee Details</h5>
            <div class="form-group mb-3">
                <label for="fixed_fee">Fixed Fee</label>
                <input type="number" step="0.01" id="fixed_fee" class="form-control" wire:model="fixed_fee">
                @error('fixed_fee') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        @elseif ($calculation_type === 'GREATER_OF_BASE_PERCENTAGE')
        <div class="card p-3 mb-3">
            <h5>Base vs. Percentage Fee Details</h5>
            <div class="form-group mb-3">
                <label for="base_fee">Base Fee</label>
                <input type="number" step="0.01" id="base_fee" class="form-control" wire:model="base_fee">
                @error('base_fee') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group mb-3">
                <label for="percentage_fee">Percentage Fee (%)</label>
                <input type="number" step="0.01" id="percentage_fee" class="form-control" wire:model="percentage_fee">
                @error('percentage_fee') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        @elseif ($calculation_type === 'TIERED')
        <div class="card p-3 mb-3">
            <h5>Fee Tiers</h5>
            @foreach ($tiers as $index => $tier)
            <div class="row align-items-end mb-2 border-bottom pb-2">
                <div class="col">
                    <label>Min Value</label>
                    <input type="number" step="0.01" class="form-control" wire:model="tiers.{{ $index }}.min_value">
                    @error('tiers.'.$index.'.min_value') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col">
                    <label>Max Value (optional)</label>
                    <input type="number" step="0.01" class="form-control" wire:model="tiers.{{ $index }}.max_value">
                </div>
                <div class="col">
                    <label>Fixed Fee</label>
                    <input type="number" step="0.01" class="form-control" wire:model="tiers.{{ $index }}.fixed_fee">
                </div>
                <div class="col">
                    <label>Percentage Fee</label>
                    <input type="number" step="0.01" class="form-control" wire:model="tiers.{{ $index }}.percentage_fee">
                </div>
                <div class="col-auto">
                    {{-- wire:click.prevent chama o método removeTier e impede o submit do formulário --}}
                    <button type="button" class="btn btn-danger" wire:click.prevent="removeTier({{ $index }})">Remove</button>
                </div>
            </div>
            @endforeach
            {{-- Botão para chamar o método addTier na classe PHP --}}
            <button type="button" class="btn btn-secondary mt-2" wire:click.prevent="addTier">+ Add Tier</button>
        </div>
        @endif

        <div class="d-flex justify-content-between">
            <a href="{{ route('fee-profiles.index') }}" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>
    </form>
</div>