<div class="flex flex-grow">
    <main class="flex flex-col gap-10 flex-grow place-content-center bg-[#F6F7F8] dark:bg-[#181C20]">
        <div class="mx-auto">
            <h1 class="text-3xl font-semibold">Many Notes</h1>
        </div>

        <div class="md:container md:mx-auto">
            <div class="flex flex-col gap-6 sm:mx-auto sm:w-full sm:max-w-sm p-6 sm:p-9 bg-[#FCFFFC] dark:bg-[#14171B] rounded-lg">
                <div class="text-center">
                    <x-form.session-status :status="session('status')" />
                </div>

                <div class="text-center">
                    <x-form.text>
                        {{ __('Can\'t sign in? Enter your email and we\'ll send you a link to reset your password.') }}
                    </x-form.text>
                </div>

                <x-form wire:submit="send" class="flex flex-col gap-6">
                    <x-form.input
                        name="form.email"
                        label="{{ __('Email') }}"
                        type="email"
                        required
                        autofocus
                    />

                    <x-form.submit label="{{ __('Send') }}" target="send" />
                </x-form>

                <div class="text-center">
                    <x-form.text>
                        <x-form.link wire:navigate href="{{ route('login') }}">
                            {{ __('Back to Sign in') }}
                        </x-form.link>
                    </x-form.text>
                </div>
            </div>
        </div>
    </main>
</div>
