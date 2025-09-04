<div x-data class="fixed z-50 top-4 right-4 space-y-2 pointer-events-none">
    <template x-for="t in $store.toast.list" :key="t.id">
        <div x-transition
            class="pointer-events-auto max-w-sm rounded-radius border shadow-lg p-3 flex items-start gap-3
                bg-surface border-outline text-on-surface
                dark:bg-surface-dark dark:border-outline-dark dark:text-on-surface-dark">
            <div class="mt-0.5">
                <x-ui.icon x-show="t.type==='success'" name="lucide-check-circle" class="size-5"></x-ui.icon>
                <x-ui.icon x-show="t.type==='error'" name="lucide-alert-triangle" class="size-5"></x-ui.icon>
                <x-ui.icon x-show="t.type==='warning'" name="lucide-alert-octagon" class="size-5"></x-ui.icon>
                <x-ui.icon x-show="t.type==='info'" name="lucide-info" class="size-5"></x-ui.icon>
            </div>
            <div class="text-sm" x-text="t.message"></div>
            <button type="button" class="ml-auto text-on-surface/70 hover:text-on-surface"
                @click="$store.toast.dismiss(t.id)">
                <x-ui.icon name="lucide-x" class="size-5"></x-ui.icon>
            </button>
        </div>
    </template>
</div>
