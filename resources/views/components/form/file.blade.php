@props(['name', 'label' => null, 'required' => false, 'accept' => null, 'maxSize' => null])
{{-- FilePond aktual (temporary upload → attach on submit) diinisialisasi via
     hook `.js-upload` di M0-T7 (§9, §11.2). --}}
<div class="mb-3 app-form-group">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    <input type="file" name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge(['class' => 'app-file js-upload']) }}
        @if ($accept) data-accept="{{ $accept }}" @endif
        @if ($maxSize) data-max-size="{{ $maxSize }}" @endif
        @if ($required) required @endif>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
