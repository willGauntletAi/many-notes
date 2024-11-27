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
        <div x-data="vault" x-cloak @sidebar-left-toggle.window="isSidebarOpen = !isSidebarOpen"
            class="relative flex w-full">
            <div wire:loading wire:target.except="nodeForm.name, nodeForm.content"
                class="fixed inset-0 z-40 bg-light-base-200 dark:bg-base-950">
                <div class="flex items-center justify-center h-full">
                    <x-icons.spinner class="w-5 h-5 animate-spin" />
                </div>
            </div>
            <div x-show="isSidebarOpen && isSmallDevice" @click="closeSideBar"
                class="fixed inset-0 z-20 opacity-50 bg-light-base-200 dark:bg-base-950"
                x-transition:enter="ease-out duration-300" x-transition:leave="ease-in duration-200">
            </div>
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

                                    <x-menu.item @click="$wire.dispatchTo('modals.import-file', 'open-modal')">
                                        <x-icons.arrowUpTray class="w-4 h-4" />
                                        {{ __('Import file') }}
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
                :class="{ 'md:pl-60': isSidebarOpen, '': !isSidebarOpen }" id="nodeContainer">
                <div class="flex flex-col h-full w-full max-w-[48rem] mx-auto p-4 gap-2">
                    @if ($selectedFile)
                        <div class="z-[5] bg-light-base-50 dark:bg-base-900">
                            <div class="flex justify-between">
                                <input type="text" wire:model.live.debounce.500ms="nodeForm.name"
                                    class="flex flex-grow p-0 pr-2 text-lg bg-transparent border-0 focus:ring-0 focus:outline-0" />

                                <div class="flex items-center gap-2">
                                    <span wire:loading.flex wire:target="nodeForm.name, nodeForm.content"
                                        class="flex items-center">
                                        <x-icons.spinner class="w-4 h-4 animate-spin" />
                                    </span>

                                    <button type="button" wire:click="closeFile" title="{{ __('Close file') }}">
                                        <x-icons.xMark class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            @error('nodeForm.name')
                                <p class="text-sm text-error-500" aria-live="assertive">{{ $message }}</p>
                            @enderror
                        </div>
                        @if (in_array($nodeForm->extension, App\Services\VaultFiles\Note::extensions()))
                            <x-markdownEditor />
                        @elseif (in_array($nodeForm->extension, App\Services\VaultFiles\Image::extensions()))
                            <div>
                                <img src="{{ $selectedFilePath }}" />
                            </div>
                        @elseif (in_array($nodeForm->extension, App\Services\VaultFiles\Pdf::extensions()))
                            <object type="application/pdf" data="{{ $selectedFilePath }}"
                                class="w-full h-full"></object>
                        @elseif (in_array($nodeForm->extension, App\Services\VaultFiles\Video::extensions()))
                            <video class="w-full" controls>
                                <source src="{{ $selectedFilePath }}" />
                                {{ __('Your browser does not support the video tag') }}
                            </video>
                        @elseif (in_array($nodeForm->extension, App\Services\VaultFiles\Audio::extensions()))
                            <div class="flex items-start justify-center w-full">
                                <audio class="w-full" controls>
                                    <source src="{{ $selectedFilePath }}">
                                    {{ __('Your browser does not support the audio tag') }}
                                </audio>
                            </div>
                        @endif
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
        </div>
    </x-layouts.appMain>

    <livewire:modals.add-node :$vault />
    <livewire:modals.import-file :$vault />
    <livewire:modals.edit-node :$vault />
    <livewire:modals.search-node :$vault />
    <livewire:modals.markdown-editor-search :$vault />
</div>

@script
    <script>
        Alpine.data('vault', () => ({
            isSidebarOpen: false,
            isEditMode: $wire.entangle('isEditMode'),
            selectedFile: $wire.entangle('selectedFile'),
            html: '',
            renderListitem: null,

            init() {
                this.$watch('isEditMode', value => {
                    if (value) {
                        return;
                    }
                    this.html = this.markdownToHtml();
                });

                this.$watch('selectedFile', value => {
                    if (value === null) {
                        this.html = '';
                        return;
                    }
                    this.html = this.markdownToHtml();
                });

                let markedRender = new marked.Renderer;
                markedRender.parser = new marked.Parser;
                this.renderListitem = markedRender.listitem.bind(markedRender);
            },

            isSmallDevice() {
                return window.innerWidth < 768;
            },

            closeSideBar() {
                this.isSidebarOpen = false;
            },

            toggleEditMode() {
                this.isEditMode = !this.isEditMode;
            },

            openFile(node) {
                $wire.openFile(node);

                if (this.isSmallDevice()) {
                    this.closeSideBar();
                }

                this.resetScrollPosition();
            },

            resetScrollPosition() {
                if (!Number.isInteger(this.selectedFile)) {
                    return;
                }

                let scrollElementId = this.isEditMode ? 'noteEdit' : 'nodeContainer';
                if (document.getElementById(scrollElementId) == null) {
                    return;
                }

                document.getElementById(scrollElementId).scrollTop = 0;
            },

            markdownToHtml() {
                let el = document.getElementById('noteEdit');
                let markdown = '';
                let renderListitem = this.renderListitem;
                let node = this.selectedFile;

                if (!el) {
                    return markdown;
                }

                renderer = {
                    image(token) {
                        let html = '';

                        if (token.href.startsWith('http://') || token.href.startsWith('https://')) {
                            // external images
                            html = '<img src="' + token.href + '" alt="' + token.text + '" />';
                        } else {
                            // internal images
                            html = '<img src="/files/{{ $vault->id }}?path=' + token.href + '&node=' +
                                node + '" alt="' + token.text + '" />';
                        }

                        return '<span class="flex items-center justify-center">' + html + '</span>';
                    },
                    link(token) {
                        // external links
                        if (token.href.startsWith('http://') || token.href.startsWith('https://')) {
                            return '<a href="' + token.href + '" title="' + (token.title ?? '') +
                                '" target="_blank">' + token.text + '</a>';
                        }

                        // internal links
                        return '<a href="" wire:click.prevent="openFilePath(\'' + token.href +
                            '\')" title="' + (token.title ?? '') + '">' + token.text + '</a>';
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

                return DOMPurify.sanitize(marked.parse(el.value));
            },
        }))
    </script>
@endscript
