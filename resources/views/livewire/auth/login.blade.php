<div class="flex flex-col h-dvh">
    <x-layouts.guestMain>
        <div class="text-center">
            <x-form.sessionStatus :status="session('status')" />
        </div>

        <x-form wire:submit="send" class="flex flex-col gap-6">
            <x-form.input name="form.email" label="{{ __('Email') }}" type="email" required autofocus />

            <x-form.input name="form.password" label="{{ __('Password') }}" type="password" required />

            <x-form.checkbox name="form.remember" label="{{ __('Remember me') }}" />

            <x-form.submit label="{{ __('Sign in') }}" target="send" />
        </x-form>

        <div class="flex flex-col gap-2 text-center">
            @if (Route::has('forgot.password'))
                <x-form.text>
                    <x-form.link wire:navigate href="{{ route('forgot.password') }}">
                        {{ __('Forgot your password?') }}
                    </x-form.link>
                </x-form.text>
            @endif

            @if (Route::has('register'))
                <x-form.text>
                    {{ __('Don\'t have an account?') }}

                    <x-form.link wire:navigate href="{{ route('register') }}">
                        {{ __('Sign up') }}
                    </x-form.link>
                </x-form.text>
            @endif
        </div>
    </x-layouts.guestMain>
</div>
