@extends('installer.layout')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Admin Account</h2>

    <form method="POST" action="{{ route('install.admin.save') }}" class="grid gap-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Admin Name</label>
            <input name="name" value="{{ old('name') }}" class="w-full border rounded p-2">
            @error('name')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Admin Email</label>
            <input name="email" value="{{ old('email') }}" class="w-full border rounded p-2">
            @error('email')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full border rounded p-2">
            @error('password')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" class="w-full border rounded p-2">
        </div>

        <button type="submit" class="px-4 py-2 rounded-lg bg-black text-white">Continue</button>
    </form>
@endsection
