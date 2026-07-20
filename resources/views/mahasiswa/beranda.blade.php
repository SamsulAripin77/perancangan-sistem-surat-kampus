@extends('layouts.mahasiswa', ['title' => __('common.beranda')])

@section('content')
    {{-- Placeholder — beranda mahasiswa (permohonan aktif + tombol Ajukan)
         dibangun di M1-T12. --}}
    <x-ui.card :title="__('common.beranda')">
        <p class="mb-0">{{ config('app.name') }}</p>
    </x-ui.card>
@endsection
