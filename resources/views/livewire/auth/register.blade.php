<div class="flex flex-grow">
    <x-layouts.guestMain>
        <x-form wire:submit="send" class="flex flex-col gap-6">
            <x-form.input
                name="form.name"
                label="{{ __('Name') }}"
                type="text"
                required
                autofocus
            />

            <x-form.input
                name="form.email"
                label="{{ __('Email') }}"
                type="email"
                required
            />

            <x-form.input
                name="form.password"
                label="{{ __('Password') }}"
                type="password"
                required
            />

            <x-form.input
                name="form.password_confirmation"
                label="{{ __('Confirm password') }}"
                type="password"
                required
            />

            <x-form.submit label="{{ __('Register') }}" target="send" />
        </x-form>

        <div class="text-center">
            <x-form.text>
                {{ __('Already registered?') }}

                <x-form.link wire:navigate href="{{ route('login') }}">
                    {{ __('Sign in') }}
                </x-form.link>
            </x-form.text>
        </div>
    </x-layouts.guestMain>
</div>
