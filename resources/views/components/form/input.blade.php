@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'errorBag' => 'default'])
<div class="mb-3 app-form-group">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-control form-control-sm app-input']) }}
        value="{{ old($name, $value) }}"
        @if ($required) required @endif>
    @error($name, $errorBag)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
