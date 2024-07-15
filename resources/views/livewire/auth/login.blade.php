<div class="flex flex-grow">
    <main class="flex flex-col gap-10 flex-grow place-content-center bg-[#F6F7F8] dark:bg-[#181C20]">
        <div class="mx-auto">
            <h1 class="text-3xl font-semibold">Many Notes</h1>
        </div>

        <div class="md:container md:mx-auto">
            <div class="flex flex-col gap-6 sm:mx-auto sm:w-full sm:max-w-sm p-6 sm:p-10 bg-[#FCFFFC] dark:bg-[#14171B] rounded-lg">
                <div class="text-center">
                    <x-form.session-status :status="session('status')" />
                </div>

                <x-form wire:submit="send" class="flex flex-col gap-6">
                    <x-form.input
                        name="form.email"
                        label="{{ __('Email') }}"
                        type="email"
                        required
                        autofocus
                    />

                    <x-form.input
                        name="form.password"
                        label="{{ __('Password') }}"
                        type="password"
                        required
                    />

                    <x-form.checkbox
                        name="form.remember"
                        label="{{ __('Remember me') }}"
                    />

                    <x-form.submit label="{{ __('Sign in') }}" target="send" />
                </x-form>

                <div class="flex flex-col gap-2 text-center">
                    @if (Route::has('forgot.password'))
                        <x-form.link wire:navigate href="{{ route('forgot.password') }}">
                            {{ __('Forgot your password?') }}
                        </x-form.link>
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
            </div>
        </div>
    </main>
</div>
