@extends('layouts.app', ['title' => __('import.preview_title')])

@php
    $badge = ['valid' => 'success', 'duplicate' => 'warning', 'invalid' => 'danger'];
@endphp

@section('content')
    <x-ui.card :title="__('import.preview_title')">
        <div class="alert alert-info py-2">
            {{ __('import.preview_summary', ['valid' => $valid, 'skipped' => $skipped]) }}
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped app-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('user.nim') }}</th>
                        <th>{{ __('user.nama') }}</th>
                        <th>{{ __('user.email') }}</th>
                        <th>{{ __('user.prodi') }}</th>
                        <th>{{ __('import.row_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['line'] }}</td>
                            <td>{{ $row['nim'] }}</td>
                            <td>{{ $row['nama'] }}</td>
                            <td>{{ $row['email'] }}</td>
                            <td>{{ $row['prodi'] }}</td>
                            <td>
                                <span class="badge bg-{{ $badge[$row['status']] ?? 'secondary' }}">
                                    {{ __("import.status_{$row['status']}") }}
                                </span>
                                @if ($row['message'])
                                    <div class="small text-body-secondary">{{ $row['message'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.user.import.store') }}" class="d-flex gap-2 mt-2">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <a href="{{ route('admin.user.import.form') }}" class="btn btn-secondary btn-sm">{{ __('import.cancel') }}</a>
            <button type="submit" class="btn btn-primary btn-sm app-btn" @disabled($valid === 0)>
                <i class="fas fa-file-import"></i> {{ __('import.commit_btn', ['valid' => $valid]) }}
            </button>
        </form>
    </x-ui.card>
@endsection
