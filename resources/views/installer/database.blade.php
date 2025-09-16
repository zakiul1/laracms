@extends('installer.layout')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Database Setup</h2>

    @if ($errors->has('db'))
        <div class="p-3 rounded bg-red-50 text-red-700 mb-3">{{ $errors->first('db') }}</div>
    @endif

    <form method="POST" action="{{ route('install.database.save') }}" class="grid gap-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">DB Host</label>
            <input name="db_host" value="{{ old('db_host', $defaults['DB_HOST']) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">DB Port</label>
            <input name="db_port" value="{{ old('db_port', $defaults['DB_PORT']) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">DB Name</label>
            <input name="db_name" value="{{ old('db_name', $defaults['DB_DATABASE']) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">DB Username</label>
            <input name="db_user" value="{{ old('db_user', $defaults['DB_USERNAME']) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">DB Password</label>
            <input type="password" name="db_pass" value="{{ old('db_pass', $defaults['DB_PASSWORD']) }}"
                class="w-full border rounded p-2">
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-black text-white">Save & Continue</button>
            <button type="button" id="testBtn" class="px-4 py-2 rounded-lg bg-gray-200">Test Connection</button>
            <span id="testResult" class="text-sm"></span>
        </div>
    </form>

    <script>
        document.getElementById('testBtn').addEventListener('click', async () => {
            const form = document.querySelector('form');
            const fd = new FormData(form);
            const body = new URLSearchParams({
                db_host: fd.get('db_host'),
                db_port: fd.get('db_port'),
                db_name: fd.get('db_name'),
                db_user: fd.get('db_user'),
                db_pass: fd.get('db_pass') || ''
            });
            const res = await fetch('{{ route('install.database.test') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body
            });
            const json = await res.json();
            document.getElementById('testResult').textContent = json.ok ? '✅ Connection OK' :
                '❌ Connection failed';
        });
    </script>
@endsection
