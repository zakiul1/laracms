<div x-data x-show="$store.confirm.open" x-cloak x-transition.opacity.duration.150ms
    class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/30" @click="$store.confirm.cancel()"></div>

    <div
        class="relative w-full max-w-sm rounded-radius border p-4
              bg-surface border-outline text-on-surface
              dark:bg-surface-dark dark:border-outline-dark dark:text-on-surface-dark">
        <h3 class="text-base font-semibold mb-2">Confirm delete</h3>
        <p class="text-sm mb-4" x-text="$store.confirm.message"></p>
        <div class="flex justify-end gap-2">
            <button type="button" class="px-3 py-1.5 rounded-radius border border-outline dark:border-outline-dark"
                @click="$store.confirm.cancel()">Cancel</button>
            <button type="button" class="px-3 py-1.5 rounded-radius bg-red-600 text-white"
                @click="$store.confirm.proceed()">Delete</button>
        </div>
    </div>
</div>
