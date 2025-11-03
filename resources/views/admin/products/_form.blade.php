<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label">Product Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
            id="name" name="name"
            value="{{ old('name', $product->name) }}"
            placeholder="e.g., Monthly Subscription" required>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="price" class="form-label">Price (R$)</label>
        <input type="number" class="form-control @error('price') is-invalid @enderror"
            id="price" name="price"
            value="{{ old('price', $product->price) }}"
            step="0.01" min="0.01" placeholder="e.g., 99.90" required>
        @error('price')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror"
            id="description" name="description"
            rows="3" placeholder="Briefly describe the product">{{ old('description', $product->description) }}</textarea>
        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            <option value="active" @selected(old('status', $product->status) == 'active')>Active</option>
            <option value="inactive" @selected(old('status', $product->status) == 'inactive')>Inactive</option>
        </select>
        @error('status')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>