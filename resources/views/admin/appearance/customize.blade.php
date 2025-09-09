@extends('admin.layout', ['title' => 'Customize'])

@section('content')
    <div x-data="customizer(@js($schema), @js($values))" class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-4 border rounded-radius p-4 space-y-4 max-h-[80vh] overflow-auto">
            <h2 class="font-semibold">Customize: <span class="opacity-80">{{ $slug }}</span></h2>

            <template x-for="panel in schema.panels" :key="panel.title">
                <div class="border rounded p-3">
                    <div class="font-medium mb-2" x-text="panel.title"></div>

                    <template x-for="field in panel.fields" :key="field.key">
                        <label class="block text-sm mb-3">
                            <span class="block mb-1" x-text="field.label"></span>

                            <template x-if="field.type === 'text' || field.type === 'image' || field.type === 'number'">
                                <input :type="field.type === 'number' ? 'number' : 'text'"
                                    class="w-full rounded-radius border px-3 py-2" :name="field.key"
                                    x-model="form[field.key]">
                            </template>

                            <template x-if="field.type === 'color'">
                                <input type="color" class="w-16 h-10 rounded" :name="field.key"
                                    x-model="form[field.key]">
                            </template>

                            <template x-if="field.type === 'select'">
                                <select class="w-full rounded-radius border px-3 py-2" :name="field.key"
                                    x-model="form[field.key]">
                                    <template x-for="opt in (field.options || [])" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </template>

                            <template x-if="field.type === 'checkbox'">
                                <input type="checkbox" :name="field.key" :checked="form[field.key] ? true : false"
                                    @change="form[field.key] = $event.target.checked">
                            </template>
                        </label>
                    </template>
                </div>
            </template>

            <div class="flex items-center gap-2 pt-3">
                <button @click="save()" class="px-4 py-2 rounded-radius border">Save</button>
                <button @click="reset()" class="px-4 py-2 rounded-radius border">Reset</button>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 border rounded-radius overflow-hidden">
            <iframe x-ref="frame" src="{{ route('admin.appearance.customize.preview') }}" class="w-full h-[80vh]"></iframe>
        </div>
    </div>

    @push('scripts')
        <script>
            function customizer(schema, values) {
                return {
                    schema,
                    form: {
                        ...values
                    },

                    // --- helpers ---
                    // Ensure we never send a Proxy/Function/DOM node through postMessage
                    toPlain(value) {
                        try {
                            return JSON.parse(JSON.stringify(value));
                        } catch (e) {
                            // very rare fallback
                            const deep = (v) => {
                                if (v === null || typeof v !== 'object') return v;
                                if (Array.isArray(v)) return v.map(deep);
                                // skip DOM nodes / functions
                                const out = {};
                                for (const k in v) {
                                    const val = v[k];
                                    if (typeof val === 'function' || (val && val.nodeType)) continue;
                                    out[k] = deep(val);
                                }
                                return out;
                            };
                            return deep(value);
                        }
                    },

                    postUpdate() {
                        const payload = this.toPlain(this.form);
                        this.$refs.frame?.contentWindow?.postMessage({
                                type: 'customize:update',
                                payload
                            },
                            window.location.origin
                        );
                    },

                    // --- actions ---
                    save() {
                        // Build body and encode booleans as 1/0 so PHP casts reliably
                        const body = new URLSearchParams();
                        for (const [k, v] of Object.entries(this.form)) {
                            if (typeof v === 'boolean') body.append(k, v ? '1' : '0');
                            else body.append(k, v ?? '');
                        }

                        fetch(@js(route('admin.appearance.customize.save')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': @js(csrf_token()),
                                'Accept': 'application/json'
                            },
                            body
                        }).then(() => this.postUpdate());
                    },

                    reset() {
                        fetch(@js(route('admin.appearance.customize.reset')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': @js(csrf_token()),
                                'Accept': 'application/json'
                            },
                        }).then(() => {
                            // Reset UI to defaults from schema
                            const defaults = {};
                            for (const p of this.schema.panels) {
                                for (const f of p.fields) defaults[f.key] = (f.default ?? (f.type === 'checkbox' ?
                                    false : ''));
                            }
                            this.form = defaults;
                            this.postUpdate();
                        });
                    },

                    refresh() {
                        this.postUpdate();
                    },

                    init() {
                        // Initial sync after iframe loads
                        setTimeout(() => this.postUpdate(), 350);
                    }
                }
            }
        </script>
    @endpush
@endsection
