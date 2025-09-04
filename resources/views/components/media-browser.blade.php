<div id="media-browser-config" data-csrf="{{ csrf_token() }}" data-list="{{ route('admin.media.list') }}"
    data-upload="{{ route('admin.media.upload') }}"
    data-show-template="{{ route('admin.media.show', ['media' => 'MEDIA_ID']) }}"
    data-meta-template="{{ route('admin.media.meta', ['media' => 'MEDIA_ID']) }}"
    data-delete-template="{{ route('admin.media.delete', ['media' => 'MEDIA_ID']) }}"
    data-cats="{{ route('admin.media.categories.json') }}"></div>

<div id="media-browser-root" class="hidden"></div>

<script>
    (function() {
        // ----------------- Config from data attrs -----------------
        var cfgEl = document.getElementById('media-browser-config');
        var CFG = {
            csrf: (cfgEl && cfgEl.dataset.csrf) || '',
            routes: {
                list: (cfgEl && cfgEl.dataset.list) || '',
                upload: (cfgEl && cfgEl.dataset.upload) || '',
                showT: (cfgEl && cfgEl.dataset.showTemplate) || '',
                metaT: (cfgEl && cfgEl.dataset.metaTemplate) || '',
                delT: (cfgEl && cfgEl.dataset.deleteTemplate) || '',
                cats: (cfgEl && cfgEl.dataset.cats) || ''
            }
        };

        // ----------------- Tiny DOM helpers -----------------
        function h(tag, attrs) {
            var el = document.createElement(tag);
            if (attrs) {
                for (var k in attrs) {
                    if (!Object.prototype.hasOwnProperty.call(attrs, k)) continue;
                    if (k === 'class') el.className = attrs[k];
                    else el.setAttribute(k, attrs[k]);
                }
            }
            for (var i = 2; i < arguments.length; i++) {
                var c = arguments[i];
                if (c == null) continue;
                if (c instanceof Node) el.appendChild(c);
                else el.appendChild(document.createTextNode(String(c)));
            }
            return el;
        }

        function btn(label, cls, onClick) {
            var b = h('button', {
                class: cls
            }, label);
            if (onClick) b.addEventListener('click', onClick);
            return b;
        }

        function bytes(n) {
            if (n == null) return '';
            var u = ['B', 'KB', 'MB', 'GB'],
                i = 0;
            while (n >= 1024 && i < u.length - 1) {
                n /= 1024;
                i++;
            }
            return (i > 1 ? n.toFixed(2) : Math.round(n)) + ' ' + u[i];
        }

        function isImg(mime) {
            return (mime || '').toLowerCase().indexOf('image/') === 0;
        }

        function spinner(cls) {
            return h('div', {
                class: 'inline-block animate-spin rounded-full border-2 border-current border-r-transparent ' +
                    (cls || 'w-5 h-5')
            });
        }

        // ----------------- State -----------------
        var modal = null,
            backdrop = null,
            overlay = null;
        var listWrap, paging, side, prog, footCount, insertBtn, sentinel;
        var resolvePromise = null;

        function defState(opts) {
            return {
                tab: 'library',
                q: '',
                type: 'all',
                category: 'all',
                view: 'grid',
                page: 1,
                per_page: 40,
                last_page: 1,
                loading: false,
                multiple: !!(opts && opts.multiple),
                selected: new Map(),
                onSelect: (opts && typeof opts.onSelect === 'function') ? opts.onSelect : null,
                cats: []
            };
        }
        var state = null;

        // ----------------- API -----------------
        function fetchList() {
            var p = new URLSearchParams();
            p.set('q', state.q);
            p.set('type', state.type);
            if (state.category !== 'all') p.set('category_id', String(state.category));
            p.set('page', String(state.page));
            p.set('per_page', String(state.per_page));
            return fetch(CFG.routes.list + '?' + p.toString(), {
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(function(r) {
                    return r.json();
                });
        }

        function fetchShow(id) {
            var url = CFG.routes.showT.replace('MEDIA_ID', String(id));
            return fetch(url, {
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(function(r) {
                    return r.json();
                });
        }

        function saveMeta(id, payload) {
            var url = CFG.routes.metaT.replace('MEDIA_ID', String(id));
            return fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': CFG.csrf,
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify(payload || {})
            }).then(function(r) {
                return r.json();
            });
        }

        function deleteItem(id) {
            var url = CFG.routes.delT.replace('MEDIA_ID', String(id));
            return fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CFG.csrf,
                    Accept: 'application/json'
                }
            });
        }

        function fetchCats() {
            if (!CFG.routes.cats) return Promise.resolve([]);
            return fetch(CFG.routes.cats, {
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(function(r) {
                    return r.ok ? r.json() : [];
                })
                .catch(function() {
                    return [];
                });
        }

        function uploadFiles(files) {
            var form = new FormData();
            Array.prototype.forEach.call(files, function(f) {
                form.append('files[]', f);
            });
            form.append('_token', CFG.csrf);
            return fetch(CFG.routes.upload, {
                    method: 'POST',
                    body: form
                })
                .then(function(r) {
                    return r.json();
                });
        }

        // ----------------- UI helpers -----------------
        function showOverlay(text) {
            if (!overlay) return;
            overlay.classList.remove('hidden');
            var t = overlay.querySelector('[data-txt]');
            if (t) t.textContent = text || 'Loading…';
        }

        function hideOverlay() {
            if (overlay) overlay.classList.add('hidden');
        }

        function buildThumb(item) {
            var outer = h('div', {
                class: 'border rounded overflow-hidden cursor-pointer hover:shadow',
                'data-media-id': String(item.id)
            });
            var box = h('div', {
                class: 'bg-gray-50 flex items-center justify-center'
            });
            box.style.aspectRatio = '1/1';
            if (isImg(item.mime)) {
                var img = h('img', {
                    class: 'object-cover w-full h-full'
                });
                img.src = item.thumb || item.url || '';
                box.appendChild(img);
            } else {
                box.appendChild(h('div', {
                    class: 'text-xs text-gray-500 p-4 break-all'
                }, item.mime || 'file'));
            }
            var cap = h('div', {
                class: 'p-2 text-xs truncate'
            }, item.filename || ('#' + item.id));
            outer.appendChild(box);
            outer.appendChild(cap);
            outer.addEventListener('click', function() {
                toggleSelect(item);
            });
            return outer;
        }

        function buildRow(item) {
            var row = h('div', {
                class: 'grid grid-cols-12 gap-2 items-center p-2 border-b cursor-pointer hover:bg-gray-50',
                'data-media-id': String(item.id)
            });
            var c1 = h('div', {
                class: 'col-span-1'
            });
            var img = h('img', {
                class: 'w-10 h-10 object-cover rounded'
            });
            img.src = item.thumb || item.url || '';
            c1.appendChild(img);
            var c2 = h('div', {
                class: 'col-span-5 truncate'
            }, item.filename || ('#' + item.id));
            var c3 = h('div', {
                class: 'col-span-2 text-xs'
            }, item.mime || '');
            var c4 = h('div', {
                class: 'col-span-2 text-xs'
            }, bytes(item.size));
            var wh = '';
            if (item.width && item.height) wh = String(item.width) + '×' + String(item.height);
            var c5 = h('div', {
                class: 'col-span-2 text-xs'
            }, wh);
            row.appendChild(c1);
            row.appendChild(c2);
            row.appendChild(c3);
            row.appendChild(c4);
            row.appendChild(c5);
            row.addEventListener('click', function() {
                toggleSelect(item);
            });
            return row;
        }

        function buildPreviewSidebar(item) {
            var wrap = h('div', {
                class: 'flex flex-col h-full overflow-hidden'
            });

            var previewBox = h('div', {
                class: 'p-3 shrink-0'
            });
            if (isImg(item.mime)) {
                var im = h('img', {
                    class: 'w-full object-contain rounded'
                });
                im.src = item.url || '';
                previewBox.appendChild(im);
            } else {
                previewBox.appendChild(h('div', {
                    class: 'border rounded p-4 text-xs text-gray-600 break-all'
                }, item.filename || ''));
            }

            var formScroll = h('div', {
                class: 'p-3 overflow-y-auto grow'
            });
            var form = h('form', {
                class: 'space-y-2'
            });

            var lt = h('label', {
                class: 'block text-xs text-gray-500'
            }, 'Title');
            var it = h('input', {
                class: 'w-full border rounded px-2 py-1',
                name: 'title'
            });
            it.value = item.title || '';
            var la = h('label', {
                class: 'block text-xs text-gray-500 mt-2'
            }, 'Alt');
            var ia = h('input', {
                class: 'w-full border rounded px-2 py-1',
                name: 'alt'
            });
            ia.value = item.alt || '';
            var lc = h('label', {
                class: 'block text-xs text-gray-500 mt-2'
            }, 'Caption');
            var ic = h('textarea', {
                class: 'w-full border rounded px-2 py-1',
                name: 'caption',
                rows: '2'
            }, item.caption || '');
            var ld = h('label', {
                class: 'block text-xs text-gray-500 mt-2'
            }, 'Description');
            var idd = h('textarea', {
                class: 'w-full border rounded px-2 py-1',
                name: 'description',
                rows: '3'
            }, item.description || '');

            var actions = h('div', {
                class: 'flex items-center gap-2 mt-3'
            });
            var saveB = btn('Save', 'px-3 py-1 border rounded', function(e) {
                e.preventDefault();
                saveMeta(item.id, {
                        title: it.value,
                        alt: ia.value,
                        caption: ic.value,
                        description: idd.value
                    })
                    .then(function() {
                        loadLibrary(true);
                    });
            });
            var delB = btn('Delete', 'px-3 py-1 border rounded text-red-600', function(e) {
                e.preventDefault();
                if (confirm('Delete permanently?')) {
                    deleteItem(item.id).then(function() {
                        state.selected.delete(item.id);
                        loadLibrary(true);
                    });
                }
            });
            actions.appendChild(saveB);
            actions.appendChild(delB);

            var meta = h('div', {
                class: 'text-xs text-gray-500 mt-3'
            });
            meta.appendChild(document.createTextNode('Filename: ' + (item.filename || '')));
            meta.appendChild(h('br'));
            meta.appendChild(document.createTextNode('Type: ' + (item.mime || '')));
            meta.appendChild(h('br'));
            meta.appendChild(document.createTextNode('Size: ' + bytes(item.size)));
            if (item.width && item.height) {
                meta.appendChild(h('br'));
                meta.appendChild(document.createTextNode('Dimensions: ' + item.width + '×' + item.height));
            }

            form.appendChild(lt);
            form.appendChild(it);
            form.appendChild(la);
            form.appendChild(ia);
            form.appendChild(lc);
            form.appendChild(ic);
            form.appendChild(ld);
            form.appendChild(idd);
            form.appendChild(actions);
            form.appendChild(meta);

            formScroll.appendChild(form);
            wrap.appendChild(previewBox);
            wrap.appendChild(formScroll);
            return wrap;
        }

        // ----------------- Render modal -----------------
        function renderModal() {
            backdrop = h('div', {
                class: 'fixed inset-0 bg-black/50 z-40'
            });
            backdrop.addEventListener('click', close);

            modal = h('div', {
                class: 'fixed inset-0 z-50 flex items-center justify-center'
            });
            var shell = h('div', {
                class: 'relative bg-white w-[95vw] max-w-6xl h-[85vh] rounded-xl shadow-xl overflow-hidden flex flex-col'
            });

            overlay = h('div', {
                    class: 'absolute inset-0 z-10 hidden bg-white/70 backdrop-blur-[1px] flex items-center justify-center'
                },
                h('div', {
                    class: 'flex items-center gap-3 text-sm text-gray-700'
                }, spinner('w-6 h-6'), h('span', {
                    'data-txt': '1'
                }, 'Loading…'))
            );
            shell.appendChild(overlay);

            // Header
            var head = h('div', {
                class: 'p-3 border-b flex items-center gap-2 shrink-0'
            });
            head.appendChild(h('div', {
                class: 'font-semibold'
            }, 'Media'));
            var headRight = h('div', {
                class: 'ml-auto flex items-center gap-2'
            });
            var bUpload = btn('Upload Files', 'px-3 py-1 border rounded', function() {
                switchTab('upload');
            });
            var bLib = btn('Media Library', 'px-3 py-1 border rounded', function() {
                switchTab('library');
            });
            var bClose = btn('Close', 'px-3 py-1 border rounded', close);
            headRight.appendChild(bUpload);
            headRight.appendChild(bLib);
            headRight.appendChild(bClose);
            head.appendChild(headRight);

            // Body
            var body = h('div', {
                class: 'flex-1 grid grid-cols-12 overflow-hidden'
            });

            // Main
            var main = h('div', {
                class: 'col-span-8 border-r flex flex-col overflow-hidden'
            });

            // Toolbar
            var toolbar = h('div', {
                class: 'p-3 border-b flex items-center gap-2 shrink-0'
            });
            var q = h('input', {
                class: 'border rounded px-3 py-2 w-64',
                placeholder: 'Search…'
            });
            q.value = state.q;
            q.addEventListener('input', function(e) {
                state.q = e.target.value;
                state.page = 1;
                if (state.tab === 'library') loadLibrary(true);
            });

            var typeSel = h('select', {
                class: 'border rounded px-2 py-2'
            });
            [
                ['all', 'All Media'],
                ['image', 'Images'],
                ['video', 'Video'],
                ['audio', 'Audio'],
                ['doc', 'Docs']
            ].forEach(function(o) {
                var opt = h('option', {
                    value: o[0]
                }, o[1]);
                if (state.type === o[0]) opt.selected = true;
                typeSel.appendChild(opt);
            });
            typeSel.addEventListener('change', function(e) {
                state.type = e.target.value;
                state.page = 1;
                loadLibrary(true);
            });

            // Category filter (appears when endpoint returns items)
            var catSelWrap = h('div', {
                class: 'hidden',
                'data-cats': '1'
            });
            var catSel = h('select', {
                class: 'border rounded px-2 py-2'
            });
            catSel.addEventListener('change', function(e) {
                state.category = e.target.value;
                state.page = 1;
                loadLibrary(true);
            });
            catSelWrap.appendChild(catSel);

            var viewWrap = h('div', {
                class: 'ml-auto flex items-center gap-2'
            });
            var bGrid = btn('Grid', 'px-2 py-1 border rounded', function() {
                state.view = 'grid';
                renderLibrary([], true);
                bGrid.classList.add('bg-gray-100');
                bList.classList.remove('bg-gray-100');
            });
            var bList = btn('List', 'px-2 py-1 border rounded', function() {
                state.view = 'list';
                renderLibrary([], true);
                bList.classList.add('bg-gray-100');
                bGrid.classList.remove('bg-gray-100');
            });
            if (state.view === 'grid') bGrid.classList.add('bg-gray-100');
            else bList.classList.add('bg-gray-100');

            toolbar.appendChild(q);
            toolbar.appendChild(typeSel);
            toolbar.appendChild(catSelWrap);
            toolbar.appendChild(viewWrap);
            viewWrap.appendChild(bGrid);
            viewWrap.appendChild(bList);

            // Upload tab
            var uploadBox = h('div', {
                id: 'mb-upload',
                class: state.tab === 'upload' ? 'block p-6 overflow-auto grow' : 'hidden'
            });
            var drop = h('div', {
                class: 'border-2 border-dashed rounded p-8 text-center'
            });
            drop.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            drop.addEventListener('drop', function(e) {
                e.preventDefault();
                if (e.dataTransfer && e.dataTransfer.files) handleFiles(e.dataTransfer.files);
            });
            drop.appendChild(h('div', {
                class: 'text-sm mb-2'
            }, 'Drag & drop files here'));
            drop.appendChild(h('div', {
                class: 'text-xs text-gray-500 mb-4'
            }, 'or'));
            var lbl = h('label', {
                class: 'inline-flex items-center gap-2 px-3 py-2 border rounded cursor-pointer'
            }, 'Select Files', spinner('w-4 h-4 hidden'));
            var file = h('input', {
                type: 'file',
                class: 'hidden',
                multiple: 'multiple'
            });
            file.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });
            lbl.appendChild(file);
            drop.appendChild(lbl);
            prog = h('div', {
                id: 'mb-upload-progress',
                class: 'mt-4 space-y-2 text-left'
            });
            uploadBox.appendChild(drop);
            uploadBox.appendChild(prog);

            // Library tab
            var libBox = h('div', {
                id: 'mb-library',
                class: state.tab === 'library' ? 'flex-1 flex flex-col overflow-hidden' : 'hidden'
            });
            listWrap = h('div', {
                class: 'flex-1 overflow-auto',
                id: 'mb-list'
            });
            paging = h('div', {
                class: 'p-3 border-t flex items-center justify-between shrink-0',
                id: 'mb-paging'
            });
            sentinel = h('div', {
                id: 'mb-sentinel',
                class: 'h-1'
            });
            listWrap.appendChild(sentinel);
            libBox.appendChild(listWrap);
            libBox.appendChild(paging);

            main.appendChild(toolbar);
            main.appendChild(uploadBox);
            main.appendChild(libBox);

            // Sidebar (independent scroll)
            side = h('div', {
                class: 'col-span-4 flex flex-col h-full overflow-hidden'
            });
            side.appendChild(h('div', {
                class: 'p-4 text-sm text-gray-500'
            }, 'Select an item to see details.'));

            // Footer (sticky)
            var foot = h('div', {
                class: 'p-3 border-t flex items-center justify-between shrink-0 bg-white'
            });
            footCount = h('span', {
                class: 'text-sm'
            }, '0 selected');
            insertBtn = btn('Insert', 'px-4 py-2 border rounded', insertSelection);
            foot.appendChild(footCount);
            foot.appendChild(h('div', {
                class: 'flex items-center gap-2'
            }, insertBtn));

            body.appendChild(main);
            body.appendChild(side);
            shell.appendChild(head);
            shell.appendChild(body);
            shell.appendChild(foot);

            modal.appendChild(shell);
            document.body.appendChild(backdrop);
            document.body.appendChild(modal);

            // Load categories if available
            fetchCats().then(function(items) {
                if (Array.isArray(items) && items.length) {
                    catSelWrap.classList.remove('hidden');
                    while (catSel.firstChild) catSel.removeChild(catSel.firstChild);
                    catSel.appendChild(h('option', {
                        value: 'all'
                    }, 'All Categories'));
                    items.forEach(function(c) {
                        var nm = c.name || (c.term && c.term.name) || ('Category #' + c.id);
                        catSel.appendChild(h('option', {
                            value: String(c.id)
                        }, nm));
                    });
                }
            });

            // Infinite scroll
            var io = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting && state.tab === 'library' && state.page < state
                        .last_page && !state.loading) {
                        state.page += 1;
                        appendLibrary();
                    }
                });
            });
            io.observe(sentinel);
        }

        // ----------------- Render pieces -----------------
        function renderSelectionHighlights() {
            var nodes = listWrap.querySelectorAll('[data-media-id]');
            Array.prototype.forEach.call(nodes, function(el) {
                var id = +el.getAttribute('data-media-id');
                var on = state.selected.has(id);
                el.classList.toggle('ring-2', on);
                el.classList.toggle('ring-blue-400', on);
            });
            footCount.textContent = state.selected.size + ' selected';
            insertBtn.disabled = state.selected.size === 0;
        }

        function renderSidebar() {
            while (side.firstChild) side.removeChild(side.firstChild);
            if (state.selected.size !== 1) {
                side.appendChild(h('div', {
                    class: 'p-4 text-sm text-gray-500'
                }, state.selected.size ? 'Multiple items selected.' : 'Select an item to see details.'));
                return;
            }
            var id = state.selected.keys().next().value;
            fetchShow(id).then(function(item) {
                side.appendChild(buildPreviewSidebar(item));
            });
        }

        function renderPaging(meta) {
            while (paging.firstChild) paging.removeChild(paging.firstChild);
            var cp = (meta && meta.current_page) || state.page || 1;
            var lp = (meta && meta.last_page) || state.last_page || 1;
            state.last_page = lp;
            var label = h('div', null, 'Page ' + cp + ' of ' + lp);
            var controls = h('div', {
                class: 'flex gap-2'
            });
            var prev = btn('Prev', 'px-3 py-1 border rounded', function() {
                state.page = Math.max(1, state.page - 1);
                loadLibrary(true);
            });
            var next = btn('Next', 'px-3 py-1 border rounded', function() {
                state.page = Math.min(lp, state.page + 1);
                loadLibrary(true);
            });
            if (cp <= 1) prev.disabled = true;
            if (cp >= lp) next.disabled = true;
            controls.appendChild(prev);
            controls.appendChild(next);
            paging.appendChild(label);
            paging.appendChild(controls);
        }

        function renderLibrary(data, reset) {
            if (reset) {
                while (listWrap.firstChild) listWrap.removeChild(listWrap.firstChild);
                listWrap.appendChild(sentinel);
            }
            if (state.view === 'grid') {
                var grid = listWrap.querySelector('[data-grid]');
                if (!grid) {
                    grid = h('div', {
                        'data-grid': '1',
                        class: 'p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3'
                    });
                    listWrap.insertBefore(grid, sentinel);
                }
                (data || []).forEach(function(it) {
                    grid.appendChild(buildThumb(it));
                });
            } else {
                var table = listWrap.querySelector('[data-list]');
                if (!table) {
                    table = h('div', {
                        'data-list': '1',
                        class: 'min-w-full'
                    });
                    var header = h('div', {
                        class: 'grid grid-cols-12 gap-2 text-xs font-medium p-2 border-b bg-gray-50 sticky top-0'
                    });
                    header.appendChild(h('div', {
                        class: 'col-span-1'
                    }, 'Preview'));
                    header.appendChild(h('div', {
                        class: 'col-span-5'
                    }, 'Filename'));
                    header.appendChild(h('div', {
                        class: 'col-span-2'
                    }, 'Type'));
                    header.appendChild(h('div', {
                        class: 'col-span-2'
                    }, 'Size'));
                    header.appendChild(h('div', {
                        class: 'col-span-2'
                    }, 'Dimensions'));
                    table.appendChild(header);
                    listWrap.insertBefore(table, sentinel);
                }
                (data || []).forEach(function(it) {
                    table.appendChild(buildRow(it));
                });
            }
            renderSelectionHighlights();
        }

        function loadLibrary(reset) {
            state.loading = true;
            showOverlay('Loading media…');
            if (reset) state.page = Math.max(1, state.page);
            fetchList().then(function(res) {
                renderLibrary(res && res.data, true);
                renderPaging(res && res.meta);
                renderSidebar();
            }).finally(function() {
                state.loading = false;
                hideOverlay();
            });
        }

        function appendLibrary() {
            state.loading = true;
            showOverlay('Loading more…');
            fetchList().then(function(res) {
                renderLibrary(res && res.data, false);
                renderPaging(res && res.meta);
                renderSelectionHighlights();
            }).finally(function() {
                state.loading = false;
                hideOverlay();
            });
        }

        // ----------------- Actions -----------------
        function toggleSelect(item) {
            if (!state.multiple) state.selected.clear();
            if (state.selected.has(item.id)) state.selected.delete(item.id);
            else state.selected.set(item.id, item);
            renderSelectionHighlights();
            renderSidebar();
        }

        function handleFiles(fileList) {
            while (prog.firstChild) prog.removeChild(prog.firstChild);
            var row = h('div', {
                class: 'flex items-center gap-2 text-sm text-gray-600 mt-2'
            }, spinner('w-5 h-5'), 'Uploading…');
            prog.appendChild(row);
            insertBtn.disabled = true;
            showOverlay('Uploading…');

            uploadFiles(fileList).then(function(res) {
                while (prog.firstChild) prog.removeChild(prog.firstChild);
                var arr = (res && res.uploaded) || [];
                if (!arr.length) prog.appendChild(h('div', {
                    class: 'text-xs text-red-600'
                }, 'No files uploaded.'));
                arr.forEach(function(it) {
                    prog.appendChild(h('div', {
                        class: 'text-xs'
                    }, 'Uploaded: ' + (it.filename || it.url || ('#' + it.id))));
                    state.selected.set(it.id, it);
                });
                switchTab('library');
                loadLibrary(true);
            }).finally(function() {
                hideOverlay();
                insertBtn.disabled = false;
            });
        }

        function insertSelection() {
            var out = [];
            state.selected.forEach(function(v) {
                out.push(v);
            });
            if (state.onSelect) state.onSelect(out);
            if (resolvePromise) resolvePromise(out);
            close();
        }

        function switchTab(tab) {
            state.tab = tab;
            var up = modal.querySelector('#mb-upload');
            var lb = modal.querySelector('#mb-library');
            if (up && lb) {
                up.classList.toggle('hidden', tab !== 'upload');
                lb.classList.toggle('hidden', tab !== 'library');
            }
            if (tab === 'library') loadLibrary(true);
        }

        function escClose(e) {
            if (e.key === 'Escape') close();
        }

        function close() {
            if (modal) modal.remove();
            if (backdrop) backdrop.remove();
            document.removeEventListener('keydown', escClose, true);
            modal = null;
            backdrop = null;
            state = null;
        }

        // ----------------- Public entry -----------------
        window.openMediaBrowser = function(opts) {
            if (modal) {
                try {
                    close();
                } catch (_) {}
            }
            state = defState(opts || {});
            renderModal();
            showOverlay('Loading media…');
            if (state.tab === 'library') loadLibrary(true);
            document.addEventListener('keydown', escClose, true);
            return new Promise(function(res) {
                resolvePromise = res;
            });
        };
    })();
</script>
