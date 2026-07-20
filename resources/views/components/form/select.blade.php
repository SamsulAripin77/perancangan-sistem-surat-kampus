@props(['name', 'label' => null, 'options' => [], 'value' => null, 'multiple' => false, 'required' => false])
{{-- Select2 aktual diinisialisasi via hook `.js-select2` di M0-T7 (§10, §11.2). --}}
<div class="mb-3 app-form-group">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    <select name="{{ $name }}{{ $multiple ? '[]' : '' }}" id="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-select form-select-sm app-select js-select2']) }}
        @if ($multiple) multiple @endif
        @if ($required) required @endif>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected(old($name, $value) == $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
