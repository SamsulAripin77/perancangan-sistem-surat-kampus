@extends('layouts.app', ['title' => __('common.dashboard')])

@section('content')
    {{-- Placeholder — konten dashboard admin (stat card, deadline, disposisi)
         dibangun di M1-T12. --}}
    <x-ui.card :title="__('common.dashboard')">
        <p class="mb-0">{{ config('app.name') }}</p>
    </x-ui.card>
@endsection
