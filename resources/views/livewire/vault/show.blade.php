<div class="flex flex-col h-dvh">
    <x-layouts.appHeader>
        <div class="flex items-center gap-4">
            <button type="button" @click="$dispatch('sidebar-left-toggle')">
                <x-icons.folder class="w-5 h-5" />
            </button>

            <button type="button" @click="$wire.dispatchTo('modals.search-node', 'open-modal')">
                <x-icons.magnifyingGlass class="w-5 h-5" />
            </button>
        </div>

        <div class="flex items-center gap-4">
            <livewire:layout.user-menu />
        </div>
    </x-layouts.appHeader>

    <x-layouts.appMain>
        <div x-data="{
            isSidebarOpen: false,
            isEditMode: $wire.entangle('isEditMode'),
            selectedFile: $wire.entangle('selectedFile'),
            html: '',
            toggleEditMode() { this.isEditMode = !this.isEditMode },
        }" x-init="$watch('isEditMode', value => html = markdown())
        $watch('selectedFile', value => html = markdown())" x-cloak
            @sidebar-left-toggle.window="isSidebarOpen = !isSidebarOpen" class="relative flex w-full">
            <div wire:loading wire:target.except="nodeForm.name, nodeForm.content"
                class="fixed inset-0 z-40 opacity-50 bg-base-950"></div>
            <div x-show="isSidebarOpen && window.innerWidth < 768" @click="isSidebarOpen = false"
                class="fixed inset-0 z-20 opacity-50 bg-base-950" x-transition:enter="ease-out duration-300"
                x-transition:leave="ease-in duration-200"></div>

            <div class="absolute top-0 left-0 z-30 flex flex-col h-full overflow-hidden overflow-y-auto transition-all w-60 bg-light-base-200 dark:bg-base-950"
                :class="{ 'translate-x-0': isSidebarOpen, '-translate-x-full hidden': !isSidebarOpen }">
                <div class="sticky top-0 z-[5] flex justify-between p-4 bg-light-base-200 dark:bg-base-950">
                    <h3>{{ $vault->name }}</h3>

                    <div class="flex items-center">
                        <x-menu>
                            <x-menu.button>
                                <x-icons.bars3 class="w-5 h-5" />
                            </x-menu.button>

                            <x-menu.items>
                                <x-menu.close>
                                    <x-menu.item @click="$wire.dispatchTo('modals.add-node', 'open-modal')">
                                        <x-icons.documentPlus class="w-4 h-4" />

                                        {{ __('New note') }}
                                    </x-menu.item>

                                    <x-menu.item
                                        @click="$wire.dispatchTo('modals.add-node', 'open-modal', { isFile: false })">
                                        <x-icons.folderPlus class="w-4 h-4" />
                                        {{ __('New folder') }}
                                    </x-menu.item>

                                    <x-modal wire:model="showEditModal">
                                        <x-modal.open>
                                            <x-menu.item>
                                                <x-icons.pencilSquare class="w-4 h-4" />

                                                {{ __('Edit vault') }}
                                            </x-menu.item>
                                        </x-modal.open>

                                        <x-modal.panel title="{{ __('Edit vault') }}">
                                            <x-form wire:submit="update" class="flex flex-col gap-6">
                                                <x-form.input name="form.name" label="{{ __('Name') }}"
                                                    type="name" required autofocus />

                                                <div class="flex justify-end">
                                                    <x-form.submit label="{{ __('Edit') }}" target="edit" />
                                                </div>
                                            </x-form>
                                        </x-modal.panel>
                                    </x-modal>

                                    <x-menu.itemLink href="/vaults" wire:navigate>
                                        <x-icons.xMark class="w-4 h-4" />

                                        Close vault
                                        </x-menu.item>
                                </x-menu.close>
                            </x-menu.items>
                        </x-menu>
                    </div>
                </div>

                <livewire:vault.tree-view :$vault />
            </div>

            <div class="absolute top-0 bottom-0 right-0 flex flex-col w-full overflow-y-auto transition-all text-start md:pl-60"
                :class="{ 'md:pl-60': isSidebarOpen, '': !isSidebarOpen }">
                @if ($selectedFile)
                    <div class="sticky top-0 z-[5] p-4 bg-light-base-50 dark:bg-base-900">
                        <div class="flex justify-between">
                            <input type="text" wire:model.live.debounce.500ms="nodeForm.name"
                                class="flex flex-grow p-0 pr-2 text-lg bg-transparent border-0 focus:ring-0 focus:outline-0" />

                            <div class="flex items-center gap-2">
                                <span wire:loading.flex wire:target="nodeForm.name, nodeForm.content"
                                    class="flex items-center">
                                    <x-icons.spinner class="w-4 h-4 animate-spin" />
                                </span>

                                @if ($nodeForm->extension == 'md')
                                    <button type="button" x-show="isEditMode" @click="toggleEditMode"
                                        title="{{ __('Click to read') }}">
                                        <x-icons.bookOpen class="w-5 h-5" />
                                    </button>
                                    <button type="button" x-show="!isEditMode" @click="toggleEditMode"
                                        title="{{ __('Click to edit') }}">
                                        <x-icons.codeBracket class="w-5 h-5" />
                                    </button>
                                @endif

                                <button type="button" wire:click="closeFile" title="{{ __('Close file') }}">
                                    <x-icons.xMark class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        @error('nodeForm.name')
                            <p class="text-sm text-error-500" aria-live="assertive">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-grow px-4">
                        @if ($nodeForm->extension == 'md')
                            <textarea wire:model.live.debounce.500ms="nodeForm.content" x-show="isEditMode" id="noteEdit"
                                data-id="{{ $selectedFile }}" class="w-full h-full p-0 bg-transparent border-0 focus:ring-0 focus:outline-0"></textarea>

                            <div x-show="!isEditMode" x-html="html" id="noteView" class="w-full h-full markdown-body">
                            </div>
                        @elseif (in_array($nodeForm->extension, ['jpg', 'jpeg', 'png', 'gif']))
                            <div>
                                <img src="{{ $selectedFilePath }}" />
                            </div>
                        @elseif (in_array($nodeForm->extension, ['pdf']))
                            <object type="application/pdf" data="{{ $selectedFilePath }}"
                                class="w-full h-full"></object>
                        @elseif (in_array($nodeForm->extension, ['webp', 'mp4', 'avi']))
                            <video class="w-full" controls>
                                <source src="{{ $selectedFilePath }}" />
                                {{ __('Your browser does not support the video tag') }}
                            </video>
                        @elseif (in_array($nodeForm->extension, ['mp3', 'flac']))
                            <div class="flex items-start justify-center w-full">
                                <audio class="w-full" controls>
                                    <source src="{{ $selectedFilePath }}">
                                    {{ __('Your browser does not support the audio tag') }}
                                </audio>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex items-center justify-center w-full h-full gap-2">
                        <x-form.button @click="$wire.dispatchTo('modals.search-node', 'open-modal')">
                            <x-icons.magnifyingGlass class="w-4 h-4" />
                            <span class="hidden text-sm font-medium md:block">{{ __('Open file') }}</span>
                        </x-form.button>

                        <x-form.button primary @click="$wire.dispatchTo('modals.add-node', 'open-modal')">
                            <x-icons.plus class="w-4 h-4" />
                            <span class="hidden text-sm font-medium md:block">{{ __('New note') }}</span>
                        </x-form.button>
                    </div>
                @endif
            </div>
        </div>
    </x-layouts.appMain>

    <livewire:modals.add-node :$vault />
    <livewire:modals.edit-node :$vault />
    <livewire:modals.search-node :$vault />
</div>

@assets
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@endassets

<script>
    let markedRender = new marked.Renderer;
    markedRender.parser = new marked.Parser;
    let renderListitem = markedRender.listitem.bind(markedRender);

    function markdown() {
        let el = document.getElementById('noteEdit');
        let markdown = '';

        if (!el) {
            return markdown;
        }

        renderer = {
            image(token) {
                // external images
                if (token.href.startsWith('http://') || token.href.startsWith('https://')) {
                    return '<img src="' + token.href + '" alt="' + token.text + '" />';
                }

                // internal images
                return '<img src="/files/{{ $vault->id }}?path=' + token.href + '&node=' + node + '" alt="' +
                    token.text + '" />';
            },
            link(token) {
                // external links
                if (token.href.startsWith('http://') || token.href.startsWith('https://')) {
                    return '<a href="' + token.href + '" target="_blank">' + token.text + '</a>';
                }

                // internal links
                return '<a href="" wire:click.prevent="openFilePath(\'' + token.href + '\')">' + token.text +
                    '</a>';
            },
            listitem(token) {
                let html = renderListitem(token);

                if (token.task) {
                    html = html.replace('<li>', '<li class="task-list-item">');
                    html = html.replace('<input ', '<input class="task-list-item-checkbox" ');
                }

                return html;
            },
        };
        marked.use({
            renderer
        });
        markdown = marked.parse(el.value);

        return markdown;
    }
</script>
