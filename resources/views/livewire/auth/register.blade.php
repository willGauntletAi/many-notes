<div class="flex flex-grow">
    <main class="flex flex-col gap-10 flex-grow place-content-center bg-[#F6F7F8] dark:bg-[#181C20]">
        <div class="mx-auto">
            <h1 class="text-3xl font-semibold">Many Notes</h1>
        </div>

        <div class="md:container md:mx-auto">
            <div class="flex flex-col gap-6 sm:mx-auto sm:w-full sm:max-w-sm p-6 sm:p-10 bg-[#FCFFFC] dark:bg-[#14171B] rounded-lg">
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
            </div>
        </div>
    </main>
</div>
