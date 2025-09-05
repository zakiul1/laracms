@extends('admin.layout', ['title' => 'Theme Editor'])

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-semibold">Theme Editor</h1>
        <p class="text-xs opacity-70">
            Editing: <span class="font-medium">{{ $theme }}</span>
            <span class="opacity-60">({{ str_replace('\\', '/', $root) }})</span>
        </p>
    </div>

    <div id="te-app" data-tree="{{ route('admin.appearance.editor.tree') }}"
        data-open="{{ route('admin.appearance.editor.open') }}" data-save="{{ route('admin.appearance.editor.save') }}"
        data-validate="{{ route('admin.appearance.editor.validate') }}" class="grid grid-cols-12 gap-4">

        {{-- Tree / quick filter --}}
        <div
            class="col-span-12 md:col-span-4 lg:col-span-3 border border-outline dark:border-outline-dark rounded-radius overflow-hidden">
            <div
                class="px-3 py-2 border-b border-outline dark:border-outline-dark bg-surface-alt dark:bg-surface-dark-alt text-xs font-medium flex items-center gap-2">
                <span>Files</span>
                <input id="te-filter"
                    class="ml-auto text-xs px-2 py-1 border rounded-radius border-outline dark:border-outline-dark w-40"
                    placeholder="Filter (name)…" />
                <button id="te-reload"
                    class="text-xs px-2 py-1 border rounded-radius border-outline dark:border-outline-dark">Reload</button>
            </div>
            <div id="te-tree" class="max-h-[70vh] overflow-auto p-2 text-sm"></div>

            {{-- Filtered list (appears when filter text entered) --}}
            <div id="te-filter-list"
                class="hidden max-h-[70vh] overflow-auto p-2 text-sm border-t border-outline dark:border-outline-dark">
            </div>
        </div>

        {{-- Editor --}}
        <div
            class="col-span-12 md:col-span-8 lg:col-span-9 border border-outline dark:border-outline-dark rounded-radius overflow-hidden">
            {{-- Tabs --}}
            <div id="te-tabs"
                class="flex items-center gap-1 px-2 py-1 border-b border-outline dark:border-outline-dark bg-surface-alt dark:bg-surface-dark-alt overflow-x-auto">
            </div>

            <div
                class="flex items-center justify-between px-3 py-2 border-b border-outline dark:border-outline-dark bg-surface-alt dark:bg-surface-dark-alt">
                <div class="text-xs flex items-center gap-2">
                    <span class="opacity-60">Editing:</span>
                    <span id="te-current" class="font-medium">—</span>
                    <span id="te-dirty"
                        class="ml-2 text-[11px] px-2 py-0.5 rounded bg-amber-100 text-amber-800 hidden">modified</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <button id="te-wrap" class="px-2 py-1 border rounded-radius border-outline dark:border-outline-dark"
                        title="Toggle word wrap">Wrap</button>
                    <div class="flex items-center gap-1">
                        <button id="te-font-dec"
                            class="px-2 py-1 border rounded-radius border-outline dark:border-outline-dark"
                            title="Font size -">A–</button>
                        <button id="te-font-inc"
                            class="px-2 py-1 border rounded-radius border-outline dark:border-outline-dark"
                            title="Font size +">A+</button>
                    </div>
                    <button id="te-revert" class="px-2 py-1 border rounded-radius border-outline dark:border-outline-dark"
                        title="Reload from disk" disabled>Revert</button>
                    <button id="te-validate"
                        class="px-3 py-1.5 rounded-radius border border-outline dark:border-outline-dark">Validate</button>
                    <button id="te-save" class="px-3 py-1.5 rounded-radius bg-primary text-white" disabled>Save</button>
                </div>
            </div>

            <textarea id="te-editor" class="w-full min-h-[66vh] p-3 font-mono text-sm outline-none"
                placeholder="Select a file from the left tree or press Ctrl/Cmd+P to Quick Open…" spellcheck="false"></textarea>

            <div
                class="flex items-center justify-between px-3 py-2 border-t border-outline dark:border-outline-dark text-[11px]">
                <div class="opacity-70">
                    Shortcuts: <kbd>Ctrl/Cmd+S</kbd> Save · <kbd>Ctrl/Cmd+P</kbd> Quick Open · <kbd>Ctrl/Cmd+G</kbd> Go to
                    line
                </div>
                <div id="te-pos" class="opacity-70">Ln 1, Col 1</div>
            </div>
        </div>
    </div>

    {{-- Quick Open modal --}}
    <div id="te-qo" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50"></div>
        <div
            class="relative mx-auto mt-24 w-[90vw] max-w-2xl rounded-radius bg-white dark:bg-neutral-900 shadow-lg border border-outline dark:border-outline-dark">
            <input id="te-qo-input"
                class="w-full px-3 py-2 border-b border-outline dark:border-outline-dark rounded-t-radius outline-none"
                placeholder="Type to search files… (Esc to close)" />
            <div id="te-qo-results" class="max-h-[60vh] overflow-auto p-2 text-sm"></div>
        </div>
    </div>

    {{-- Toast container --}}
    <div id="te-toasts" class="fixed right-3 top-3 z-[60] space-y-2"></div>

    <script>
        (function() {
            const app = document.getElementById('te-app');
            const TREE_URL = app.dataset.tree;
            const OPEN_URL = app.dataset.open;
            const SAVE_URL = app.dataset.save;
            const VALIDATE_URL = app.dataset.validate;

            const treeEl = document.getElementById('te-tree');
            const listEl = document.getElementById('te-filter-list');
            const filterEl = document.getElementById('te-filter');
            const reloadBtn = document.getElementById('te-reload');

            const editorEl = document.getElementById('te-editor');
            const currentEl = document.getElementById('te-current');
            const dirtyEl = document.getElementById('te-dirty');
            const saveBtn = document.getElementById('te-save');
            const valBtn = document.getElementById('te-validate');
            const wrapBtn = document.getElementById('te-wrap');
            const fIncBtn = document.getElementById('te-font-inc');
            const fDecBtn = document.getElementById('te-font-dec');
            const revertBtn = document.getElementById('te-revert');
            const tabsEl = document.getElementById('te-tabs');
            const posEl = document.getElementById('te-pos');

            const qo = document.getElementById('te-qo');
            const qoInput = document.getElementById('te-qo-input');
            const qoRes = document.getElementById('te-qo-results');

            const toasts = document.getElementById('te-toasts');

            // state
            let treeData = [];
            let flatFiles = []; // [{path,name}]
            let currentPath = null;
            let original = '';
            let fontSize = 14;
            let wrap = false;
            const tabs = []; // [{path, title, dirty}]

            // helpers ----------------------------------------------------------
            function csrf() {
                const t = document.querySelector('meta[name="csrf-token"]');
                return t ? t.getAttribute('content') : '{{ csrf_token() }}';
            }

            function toast(msg, type = 'success') {
                const n = document.createElement('div');
                n.className = 'px-3 py-2 rounded-radius shadow text-sm ' + (type === 'error' ?
                    'bg-red-600 text-white' : 'bg-emerald-600 text-white');
                n.textContent = msg;
                toasts.appendChild(n);
                setTimeout(() => {
                    n.classList.add('opacity-0');
                    n.style.transition = 'opacity .3s';
                    setTimeout(() => n.remove(), 300);
                }, 1800);
            }

            function markDirty(on) {
                saveBtn.disabled = !on || !currentPath;
                dirtyEl.classList.toggle('hidden', !on);
                const t = tabs.find(x => x.path === currentPath);
                if (t) t.dirty = !!on;
                renderTabs();
                revertBtn.disabled = !currentPath;
            }

            function buildFlatList(nodes, prefix = '') {
                nodes.forEach(n => {
                    if (n.type === 'dir') buildFlatList(n.children || [], (prefix ? prefix + '/' : '') + n
                        .name);
                    else flatFiles.push({
                        path: (prefix ? prefix + '/' : '') + n.name,
                        name: n.name.toLowerCase()
                    });
                });
            }

            function renderTabs() {
                tabsEl.innerHTML = '';
                tabs.forEach(t => {
                    const b = document.createElement('button');
                    b.className =
                        'text-xs px-2 py-1 rounded-radius border border-outline dark:border-outline-dark bg-white dark:bg-neutral-900 flex items-center gap-1';
                    b.innerHTML =
                        `<span class="truncate max-w-[220px]">${t.title}${t.dirty ? ' •' : ''}</span>`;
                    if (t.path === currentPath) b.classList.add('bg-surface-alt', 'dark:bg-surface-dark-alt');
                    b.addEventListener('click', () => openFile(t.path));
                    const x = document.createElement('span');
                    x.textContent = '×';
                    x.className = 'ml-1 cursor-pointer opacity-60 hover:opacity-100';
                    x.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (t.dirty && !confirm('Close without saving changes?')) return;
                        const idx = tabs.findIndex(v => v.path === t.path);
                        tabs.splice(idx, 1);
                        if (t.path === currentPath) {
                            if (tabs[idx]) openFile(tabs[idx].path);
                            else if (tabs[idx - 1]) openFile(tabs[idx - 1].path);
                            else {
                                currentPath = null;
                                currentEl.textContent = '—';
                                editorEl.value = '';
                                original = '';
                                markDirty(false);
                                renderTabs();
                            }
                        } else renderTabs();
                    });
                    b.appendChild(x);
                    tabsEl.appendChild(b);
                });
            }

            function setEditorStyles() {
                editorEl.style.fontSize = fontSize + 'px';
                editorEl.style.whiteSpace = wrap ? 'pre-wrap' : 'pre';
                editorEl.style.wordBreak = wrap ? 'break-word' : 'normal';
            }

            function updateCaretPos() {
                const pos = editorEl.selectionStart || 0;
                const s = editorEl.value.slice(0, pos);
                const lines = s.split('\n');
                const ln = lines.length;
                const col = lines[lines.length - 1].length + 1;
                posEl.textContent = `Ln ${ln}, Col ${col}`;
            }

            function goToLine(n) {
                n = Math.max(1, parseInt(n || '1', 10));
                const lines = editorEl.value.split('\n');
                let idx = 0;
                for (let i = 0; i < n - 1 && i < lines.length; i++) idx += lines[i].length + 1; // +\n
                editorEl.focus();
                editorEl.setSelectionRange(idx, idx);
                editorEl.scrollTop = editorEl.scrollHeight * (n / Math.max(1, lines.length));
                updateCaretPos();
            }

            // tree --------------------------------------------------------------
            function buildTreeDOM(container, nodes, prefix) {
                container.innerHTML = '';
                const ul = document.createElement('ul');
                ul.className = 'space-y-1';
                container.appendChild(ul);

                nodes.forEach(n => {
                    const li = document.createElement('li');

                    if (n.type === 'dir') {
                        const summary = document.createElement('div');
                        summary.className =
                            'cursor-pointer select-none px-2 py-1 rounded hover:bg-surface-alt dark:hover:bg-surface-dark-alt';
                        summary.textContent = '📁 ' + n.name;
                        li.appendChild(summary);

                        const child = document.createElement('div');
                        child.className = 'ml-4 mt-1 hidden';
                        li.appendChild(child);

                        summary.addEventListener('click', () => {
                            if (!child.hasChildNodes()) {
                                buildTreeDOM(child, n.children || [], (prefix ? prefix + '/' : '') + n
                                    .name);
                            }
                            child.classList.toggle('hidden');
                        });
                    } else {
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className =
                            'block px-2 py-1 rounded hover:bg-surface-alt dark:hover:bg-surface-dark-alt';
                        const rel = (prefix ? prefix + '/' : '') + n.name;
                        a.textContent = '📄 ' + n.name;
                        a.addEventListener('click', (e) => {
                            e.preventDefault();
                            openFile(rel);
                        });
                        li.appendChild(a);
                    }

                    ul.appendChild(li);
                });
            }

            function loadTree(showToast = false) {
                treeEl.textContent = 'Loading…';
                listEl.classList.add('hidden');
                fetch(TREE_URL, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.ok) throw new Error('tree');
                        treeData = data.tree || [];
                        flatFiles = [];
                        buildFlatList(treeData);
                        buildTreeDOM(treeEl, treeData, '');
                        if (showToast) toast('Tree reloaded');
                    })
                    .catch(() => {
                        treeEl.textContent = 'Failed to load tree.';
                    });
            }

            // quick filter list
            filterEl.addEventListener('input', () => {
                const q = filterEl.value.trim().toLowerCase();
                if (!q) {
                    listEl.classList.add('hidden');
                    treeEl.classList.remove('hidden');
                    return;
                }
                treeEl.classList.add('hidden');
                listEl.classList.remove('hidden');
                const matches = flatFiles
                    .map(f => ({
                        path: f.path,
                        score: f.path.toLowerCase().indexOf(q)
                    }))
                    .filter(x => x.score >= 0)
                    .sort((a, b) => a.score - b.score)
                    .slice(0, 200);

                listEl.innerHTML = '';
                if (!matches.length) {
                    listEl.textContent = 'No matches';
                    return;
                }
                matches.forEach(m => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className =
                        'block px-2 py-1 rounded hover:bg-surface-alt dark:hover:bg-surface-dark-alt';
                    a.textContent = m.path;
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        openFile(m.path);
                    });
                    listEl.appendChild(a);
                });
            });

            reloadBtn.addEventListener('click', () => loadTree(true));

            // open / save -------------------------------------------------------
            function openFile(path) {
                // guard unsaved
                if (saveBtn.disabled === false && editorEl.value !== original) {
                    if (!confirm('Discard unsaved changes?')) return;
                }
                fetch(OPEN_URL + '?path=' + encodeURIComponent(path), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.ok) throw new Error('open');
                        currentPath = data.path;
                        currentEl.textContent = currentPath;
                        editorEl.value = data.contents || '';
                        original = editorEl.value;
                        markDirty(false);
                        editorEl.focus();
                        updateCaretPos();

                        // tabs: add or activate
                        let t = tabs.find(x => x.path === currentPath);
                        if (!t) {
                            t = {
                                path: currentPath,
                                title: currentPath.split('/').slice(-1)[0],
                                dirty: false
                            };
                            tabs.push(t);
                        }
                        renderTabs();
                    })
                    .catch(() => {
                        toast('Could not open file', 'error');
                    });
            }

            editorEl.addEventListener('input', () => markDirty(editorEl.value !== original));
            editorEl.addEventListener('keyup', updateCaretPos);
            editorEl.addEventListener('click', updateCaretPos);

            saveBtn.addEventListener('click', () => {
                if (!currentPath) return;
                fetch(SAVE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf(),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            path: currentPath,
                            content: editorEl.value
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.ok) throw new Error('save');
                        original = editorEl.value;
                        markDirty(false);
                        toast('Saved');
                    })
                    .catch(() => toast('Save failed', 'error'));
            });

            revertBtn.addEventListener('click', () => currentPath && openFile(currentPath));

            valBtn.addEventListener('click', () => {
                if (!currentPath) return;
                fetch(VALIDATE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf(),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            path: currentPath,
                            content: editorEl.value
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) toast('No syntax issues found');
                        else toast('Issues:\n- ' + (data.issues || []).join('\n- '), 'error');
                    })
                    .catch(() => toast('Validation failed', 'error'));
            });

            // wrap & font size ---------------------------------------------------
            wrapBtn.addEventListener('click', () => {
                wrap = !wrap;
                setEditorStyles();
                wrapBtn.classList.toggle('bg-surface-alt');
            });
            fIncBtn.addEventListener('click', () => {
                fontSize = Math.min(22, fontSize + 1);
                setEditorStyles();
            });
            fDecBtn.addEventListener('click', () => {
                fontSize = Math.max(10, fontSize - 1);
                setEditorStyles();
            });

            // Quick Open --------------------------------------------------------
            function openQuickOpen() {
                qo.classList.remove('hidden');
                qoInput.value = '';
                qoRes.innerHTML = '';
                setTimeout(() => qoInput.focus(), 10);
            }

            function closeQuickOpen() {
                qo.classList.add('hidden');
            }
            qo.addEventListener('click', (e) => {
                if (e.target === qo) closeQuickOpen();
            });
            document.addEventListener('keydown', (e) => {
                const mod = e.ctrlKey || e.metaKey;
                if (mod && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    saveBtn.click();
                }
                if (mod && e.key.toLowerCase() === 'p') {
                    e.preventDefault();
                    openQuickOpen();
                }
                if (mod && e.key.toLowerCase() === 'g') {
                    e.preventDefault();
                    const ln = prompt('Go to line:');
                    if (ln) goToLine(ln);
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !qo.classList.contains('hidden')) closeQuickOpen();
            });
            qoInput.addEventListener('input', () => {
                const q = qoInput.value.trim().toLowerCase();
                const matches = !q ? flatFiles.slice(0, 200) :
                    flatFiles
                    .map(f => ({
                        path: f.path,
                        score: f.path.toLowerCase().indexOf(q)
                    }))
                    .filter(x => x.score >= 0)
                    .sort((a, b) => a.score - b.score)
                    .slice(0, 200);

                qoRes.innerHTML = '';
                matches.forEach(m => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className =
                        'block px-3 py-2 rounded hover:bg-surface-alt dark:hover:bg-surface-dark-alt';
                    a.textContent = m.path;
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        closeQuickOpen();
                        openFile(m.path);
                    });
                    qoRes.appendChild(a);
                });
            });
            qoInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const first = qoRes.querySelector('a');
                    if (first) first.click();
                }
            });

            // init --------------------------------------------------------------
            setEditorStyles();
            loadTree();
            updateCaretPos();
        })();
    </script>
@endsection
