{{-- Section "Ubah Password" reusable (M1-T3). Dipakai halaman ubah password
     mandiri & kelak halaman Profil (M3-T8) / pengaturan akun admin.
     Submit ke Fortify user-password.update; error di bag 'updatePassword'. --}}
<form method="POST" action="{{ route('user-password.update') }}">
    @csrf
    @method('PUT')

    <x-form.input name="current_password" type="password" :label="__('auth.password_current')"
        :errorBag="'updatePassword'" required />
    <x-form.input name="password" type="password" :label="__('auth.password_new')"
        :errorBag="'updatePassword'" required />
    <x-form.input name="password_confirmation" type="password" :label="__('auth.password_confirmation')"
        :errorBag="'updatePassword'" required />

    <x-ui.button type="submit" variant="primary" icon="key">{{ __('common.simpan') }}</x-ui.button>
</form>
