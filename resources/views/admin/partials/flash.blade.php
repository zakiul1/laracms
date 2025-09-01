@if (session('status'))
    <div class="mb-3 px-3 py-2 rounded-radius bg-green-500/10 text-green-700 border border-green-500/30">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-3 px-3 py-2 rounded-radius bg-red-500/10 text-red-700 border border-red-500/30">
        <ul class="list-disc ml-5">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif
