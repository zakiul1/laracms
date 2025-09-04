{{-- resources/views/admin/posts/partials/publish-panel.blade.php (or inline) --}}
<x-post.panel title="Publish">
    {{-- We no longer show status or date-time. Controller decides. --}}
    <div class="flex gap-2">
        <button type="submit" name="action" value="save" class="px-3 py-2 rounded-radius border border-outline text-sm">
            Save Draft
        </button>
        <button type="submit" name="action" value="publish"
            class="px-3 py-2 rounded-radius bg-primary text-white text-sm">
            Publish
        </button>
    </div>
</x-post.panel>
