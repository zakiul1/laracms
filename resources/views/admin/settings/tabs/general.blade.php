@php($v = $values)
<div class="grid sm:grid-cols-2 gap-4">
    {{-- Site Title --}}
    <div>
        <label class="text-xs">Site Title</label>
        <input name="general[site_title]" value="{{ $v['site_title'] }}" class="w-full rounded border px-2 py-1 text-sm">
        <p class="text-[11px] text-red-600" x-text="errors['general.site_title']"></p>
    </div>

    {{-- Tagline --}}
    <div>
        <label class="text-xs">Tagline</label>
        <input name="general[tagline]" value="{{ $v['tagline'] }}" class="w-full rounded border px-2 py-1 text-sm">
        <p class="text-[11px] text-red-600" x-text="errors['general.tagline']"></p>
    </div>

    {{-- Favicon / Site Icon URL --}}
    <div>
        <label class="text-xs">Site Icon (URL)</label>
        <input name="general[site_icon_url]" value="{{ $v['site_icon_url'] }}"
            class="w-full rounded border px-2 py-1 text-sm" placeholder="https://.../favicon.png" inputmode="url">
        <p class="text-[11px] text-red-600" x-text="errors['general.site_icon_url']"></p>
    </div>

    {{-- Admin email --}}
    <div>
        <label class="text-xs">Administration Email Address</label>
        <input name="general[admin_email]" value="{{ $v['admin_email'] }}"
            class="w-full rounded border px-2 py-1 text-sm" inputmode="email">
        <p class="text-[11px] text-red-600" x-text="errors['general.admin_email']"></p>
    </div>

    {{-- WordPress Address (URL) --}}
    <div>
        <label class="text-xs">WordPress Address (URL)</label>
        <input name="general[wp_url]" value="{{ $v['wp_url'] }}" class="w-full rounded border px-2 py-1 text-sm"
            inputmode="url">
        <p class="text-[11px] text-red-600" x-text="errors['general.wp_url']"></p>
    </div>

    {{-- Site Address (URL) --}}
    <div>
        <label class="text-xs">Site Address (URL)</label>
        <input name="general[site_url]" value="{{ $v['site_url'] }}" class="w-full rounded border px-2 py-1 text-sm"
            inputmode="url">
        <p class="text-[11px] text-red-600" x-text="errors['general.site_url']"></p>
    </div>

    {{-- Membership toggle (always posts a value) --}}
    <div class="flex items-center gap-2">
        <input type="hidden" name="general[membership_anyone]" value="0">
        <input type="checkbox" name="general[membership_anyone]" value="1"
            {{ $v['membership_anyone'] ? 'checked' : '' }}>
        <label class="text-xs">Anyone can register</label>
        <p class="text-[11px] text-red-600" x-text="errors['general.membership_anyone']"></p>
    </div>

    {{-- Default user role --}}
    <div>
        <label class="text-xs">Default User Role</label>
        <select name="general[default_user_role]" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($roles ?? ['subscriber' => 'Subscriber', 'author' => 'Author', 'editor' => 'Editor', 'admin' => 'Administrator'] as $roleVal => $roleLabel)
                <option value="{{ $roleVal }}" @selected($v['default_user_role'] === $roleVal)>{{ $roleLabel }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['general.default_user_role']"></p>
    </div>

    {{-- Language --}}
    <div>
        <label class="text-xs">Site Language</label>
        <select name="general[language]" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($locales ?? [config('app.locale', 'en') => strtoupper(config('app.locale', 'en'))] as $code => $label)
                @php($val = is_int($code) ? $label : $code)
                <option value="{{ $val }}" @selected($v['language'] === $val)>
                    {{ is_int($code) ? strtoupper($label) : $label }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['general.language']"></p>
    </div>

    {{-- Timezone --}}
    <div>
        <label class="text-xs">Timezone</label>
        <select name="general[timezone]" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($timezones ?? [] as $tz)
                <option value="{{ $tz }}" @selected($v['timezone'] === $tz)>{{ $tz }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['general.timezone']"></p>
    </div>

    {{-- Date format --}}
    <div>
        <label class="text-xs">Date Format</label>
        <input name="general[date_format]" value="{{ $v['date_format'] }}"
            class="w-full rounded border px-2 py-1 text-sm" placeholder="Y-m-d">
        <p class="text-[11px] text-red-600" x-text="errors['general.date_format']"></p>
    </div>

    {{-- Time format --}}
    <div>
        <label class="text-xs">Time Format</label>
        <input name="general[time_format]" value="{{ $v['time_format'] }}"
            class="w-full rounded border px-2 py-1 text-sm" placeholder="H:i">
        <p class="text-[11px] text-red-600" x-text="errors['general.time_format']"></p>
    </div>

    {{-- Week starts on --}}
    <div>
        <label class="text-xs">Week Starts On</label>
        <select name="general[week_starts_on]" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($weekdays ?? [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $k => $lbl)
                <option value="{{ $k }}" @selected((int) $v['week_starts_on'] === (int) $k)>{{ $lbl }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['general.week_starts_on']"></p>
    </div>
</div>
