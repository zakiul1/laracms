<footer class="mt-16 border-t border-neutral-200">
    <div class="mx-auto max-w-6xl px-4 py-10 text-sm text-neutral-600">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                © {{ date('Y') }} {{ $settings->site_title ?? config('app.name') }}.
                <span class="opacity-75">{{ $settings->site_tagline ?? '' }}</span>
            </div>
            <div class="opacity-60">
                Powered by LaraCMS.
            </div>
        </div>
    </div>
</footer>
