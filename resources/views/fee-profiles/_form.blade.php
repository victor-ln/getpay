@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="form-group mb-3">
    <label for="name">Profile Name</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $feeProfile->name ?? '') }}" required>
</div>

<div class="form-group mb-3">
    <label for="account_id">Link to an Account (Optional)</label>
    <select name="account_id" id="account_id" class="form-control">
        <option value="">None (Global Profile)</option>
        @foreach($accounts as $account)
        <option value="{{ $account->id }}" @selected(old('account_id', $feeProfile->account_id ?? '') == $account->id)>
            {{ $account->name }}
        </option>
        @endforeach
    </select>
</div>

<div class="form-group mb-3">
    <label for="calculation_type">Calculation Type</label>
    <select name="calculation_type" id="calculation_type" class="form-control" required>
        <option value="SIMPLE_FIXED" @selected(old('calculation_type', $feeProfile->calculation_type ?? '') == 'SIMPLE_FIXED')>Simple Fixed </option>
        <option value="GREATER_OF_BASE_PERCENTAGE" @selected(old('calculation_type', $feeProfile->calculation_type ?? '') == 'GREATER_OF_BASE_PERCENTAGE')>Greater of Base and %</option>
        <option value="TIERED" @selected(old('calculation_type', $feeProfile->calculation_type ?? '') == 'TIERED')>By Value Range </option>
    </select>
</div>

{{-- Campos que aparecem condicionalmente --}}
<div id="simple_fixed_fields" class="conditional-fields">
    <div class="form-group mb-3">
        <label for="fixed_fee">Fixed Fee</label>
        <input type="number" step="0.01" name="fixed_fee" id="fixed_fee" class="form-control" value="{{ old('fixed_fee', $feeProfile->fixed_fee ?? '') }}">
    </div>
</div>

<div id="base_percentage_fields" class="conditional-fields">
    <div class="form-group mb-3">
        <label for="base_fee">Base Fee</label>
        <input type="number" step="0.01" name="base_fee" id="base_fee" class="form-control" value="{{ old('base_fee', $feeProfile->base_fee ?? '') }}">
    </div>
    <div class="form-group mb-3">
        <label for="percentage_fee">Percentage Rate (%)</label>
        <input type="number" step="0.01" name="percentage_fee" id="percentage_fee" class="form-control" value="{{ old('percentage_fee', $feeProfile->percentage_fee ?? '') }}">
    </div>
</div>

<button type="submit" class="btn btn-primary">Save</button>