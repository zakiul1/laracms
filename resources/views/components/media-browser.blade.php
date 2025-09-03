<div id="media-browser-config" data-csrf="{{ csrf_token() }}" data-list="{{ route('admin.media.list') }}"
    data-upload="{{ route('admin.media.upload') }}"
    data-show-template="{{ route('admin.media.show', ['media' => 'MEDIA_ID']) }}"
    data-meta-template="{{ route('admin.media.meta', ['media' => 'MEDIA_ID']) }}"
    data-delete-template="{{ route('admin.media.delete', ['media' => 'MEDIA_ID']) }}">
</div>

<div id="media-browser-root" class="hidden"></div>

<script>
    (function() {
        // Read config safely from data-attrs
        var cfgEl = document.getElementById('media-browser-config');
        var CFG = {
            csrf: (cfgEl && cfgEl.dataset.csrf) || '',
            routes: {
                list: (cfgEl && cfgEl.dataset.list) || '',
                upload: (cfgEl && cfgEl.dataset.upload) || '',
                showT: (cfgEl && cfgEl.dataset.showTemplate) || '',
                metaT: (cfgEl && cfgEl.dataset.metaTemplate) || '',
                delT: (cfgEl && cfgEl.dataset.deleteTemplate) || ''
            }
        };

        // Small helpers
        function h(tag, attrs) {
            var el = document.createElement(tag);
            if (attrs) {
                for (var k in attrs) {
                    if (!Object.prototype.hasOwnProperty.call(attrs, k)) continue;
                    var v = attrs[k];
                    if (k === 'class') el.className = v;
                    else el.setAttribute(k, v);
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

        function isImg(m) {
            return (m || '').toLowerCase().indexOf('image/') === 0;
        }

        // State
        var modal = null,
            backdrop = null,
            resolvePromise = null;
        var state = null;

        function defState(opts) {
            return {
                tab: 'library',
                q: '',
                type: 'all',
                view: 'grid',
                page: 1,
                per_page: 40,
                multiple: !!(opts && opts.multiple),
                selected: new Map(),
                onSelect: opts && typeof opts.onSelect === 'function' ? opts.onSelect : null
            };
        }

        // API
        function fetchList() {
            var p = new URLSearchParams();
            p.set('q', state.q);
            p.set('type', state.type);
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
            }).then(function(r) {
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

        function uploadFiles(files) {
            var form = new FormData();
            Array.prototype.forEach.call(files, function(f) {
                form.append('files[]', f);
            });
            form.append('_token', CFG.csrf);
            return fetch(CFG.routes.upload, {
                method: 'POST',
                body: form
            }).then(function(r) {
                return r.json();
            });
        }

        // DOM Builders (no innerHTML / no template strings)
        function buildThumb(item) {
            var outer = h('div', {
                class: 'border rounded overflow-hidden cursor-pointer hover:shadow',
                'data-media-id': String(item.id)
            });
            var box = h('div', {
                class: 'bg-gray-50 flex items-center justify-center'
            });
            // keep square via CSS aspect ratio
            box.style.aspectRatio = '1 / 1';

            if (isImg(item.mime)) {
                var img = h('img', {
                    class: 'object-cover w-full h-full'
                });
                img.src = item.thumb || item.url || '';
                box.appendChild(img);
            } else {
                var t = h('div', {
                    class: 'text-xs text-gray-500 p-4 break-all'
                }, item.mime || 'file');
                box.appendChild(t);
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
            var wrap = h('div', null);
            var previewBox = h('div', {
                class: 'p-3'
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
            wrap.appendChild(previewBox);

            var form = h('form', {
                class: 'space-y-2 p-3'
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
            var id = h('textarea', {
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
                    description: id.value
                }).then(function() {
                    loadLibrary();
                });
            });
            var delB = btn('Delete', 'px-3 py-1 border rounded text-red-600', function(e) {
                e.preventDefault();
                if (confirm('Delete permanently?')) {
                    deleteItem(item.id).then(function() {
                        state.selected.delete(item.id);
                        loadLibrary();
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
            form.appendChild(id);
            form.appendChild(actions);
            form.appendChild(meta);

            wrap.appendChild(form);
            return wrap;
        }

        // Render modal
        function renderModal() {
            backdrop = h('div', {
                class: 'fixed inset-0 bg-black/50 z-40'
            });
            backdrop.addEventListener('click', close);

            modal = h('div', {
                class: 'fixed inset-0 z-50 flex items-center justify-center'
            });
            var shell = h('div', {
                class: 'bg-white w-[95vw] h-[85vh] max-w-6xl rounded-xl shadow-xl overflow-hidden flex flex-col'
            });

            // Header
            var head = h('div', {
                class: 'p-3 border-b flex items-center gap-2'
            });
            head.appendChild(h('div', {
                class: 'font-semibold'
            }, 'Media'));
            var headRight = h('div', {
                class: 'ml-auto flex items-center gap-2'
            });
            headRight.appendChild(btn('Upload Files', 'px-3 py-1 border rounded', function() {
                switchTab('upload');
            }));
            headRight.appendChild(btn('Media Library', 'px-3 py-1 border rounded', function() {
                switchTab('library');
            }));
            headRight.appendChild(btn('Close', 'px-3 py-1 border rounded', close));
            head.appendChild(headRight);

            // Body grid
            var body = h('div', {
                class: 'flex-1 grid grid-cols-12'
            });

            // Main
            var main = h('div', {
                class: 'col-span-8 border-r flex flex-col'
            });

            // Toolbar
            var toolbar = h('div', {
                class: 'p-3 border-b flex items-center gap-2'
            });
            var q = h('input', {
                class: 'border rounded px-3 py-2 w-64',
                placeholder: 'Search...'
            });
            q.value = state.q;
            q.addEventListener('input', function(e) {
                state.q = e.target.value;
                state.page = 1;
                if (state.tab === 'library') loadLibrary();
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
            ].forEach(function(opt) {
                var o = h('option', {
                    value: opt[0]
                }, opt[1]);
                if (state.type === opt[0]) o.selected = true;
                typeSel.appendChild(o);
            });
            typeSel.addEventListener('change', function(e) {
                state.type = e.target.value;
                state.page = 1;
                loadLibrary();
            });
            var viewWrap = h('div', {
                class: 'ml-auto flex items-center gap-2'
            });
            var bGrid = btn('Grid', 'px-2 py-1 border rounded', function() {
                state.view = 'grid';
                renderLibrary();
                bGrid.classList.add('bg-gray-100');
                bList.classList.remove('bg-gray-100');
            });
            var bList = btn('List', 'px-2 py-1 border rounded', function() {
                state.view = 'list';
                renderLibrary();
                bList.classList.add('bg-gray-100');
                bGrid.classList.remove('bg-gray-100');
            });
            if (state.view === 'grid') bGrid.classList.add('bg-gray-100');
            else bList.classList.add('bg-gray-100');
            viewWrap.appendChild(bGrid);
            viewWrap.appendChild(bList);

            toolbar.appendChild(q);
            toolbar.appendChild(typeSel);
            toolbar.appendChild(viewWrap);

            // Upload tab
            var uploadBox = h('div', {
                id: 'mb-upload',
                class: state.tab === 'upload' ? 'block p-6' : 'hidden'
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
                class: 'inline-block px-3 py-2 border rounded cursor-pointer'
            }, 'Select Files');
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
            var prog = h('div', {
                id: 'mb-upload-progress',
                class: 'mt-4 space-y-2 text-left'
            });
            uploadBox.appendChild(drop);
            uploadBox.appendChild(prog);

            // Library tab
            var libBox = h('div', {
                id: 'mb-library',
                class: state.tab === 'library' ? 'flex-1 flex flex-col' : 'hidden'
            });
            var listWrap = h('div', {
                class: 'flex-1 overflow-auto',
                id: 'mb-list'
            });
            var paging = h('div', {
                class: 'p-3 border-t flex items-center justify-between',
                id: 'mb-paging'
            });
            libBox.appendChild(listWrap);
            libBox.appendChild(paging);

            main.appendChild(toolbar);
            main.appendChild(uploadBox);
            main.appendChild(libBox);

            // Sidebar
            var side = h('div', {
                class: 'col-span-4 flex flex-col',
                id: 'mb-sidebar'
            });
            side.appendChild(h('div', {
                class: 'p-4 text-sm text-gray-500'
            }, 'Select an item to see details.'));

            // Footer
            var foot = h('div', {
                class: 'p-3 border-t flex items-center justify-between'
            });
            var cnt = h('span', {
                class: 'text-sm',
                'data-count': '1'
            }, '0 selected');
            foot.appendChild(h('div', null, cnt));
            foot.appendChild(h('div', {
                class: 'flex items-center gap-2'
            }, btn('Insert', 'px-4 py-2 border rounded', insertSelection)));

            body.appendChild(main);
            body.appendChild(side);
            shell.appendChild(head);
            shell.appendChild(body);
            shell.appendChild(foot);
            modal.appendChild(shell);
            document.body.appendChild(backdrop);
            document.body.appendChild(modal);

            // Inner functions need references
            function renderPaging(meta) {
                while (paging.firstChild) paging.removeChild(paging.firstChild);
                var cp = (meta && meta.current_page) || 1;
                var lp = (meta && meta.last_page) || 1;
                var label = h('div', null, 'Page ' + cp + ' of ' + lp);
                var controls = h('div', {
                    class: 'flex gap-2'
                });
                var prev = btn('Prev', 'px-3 py-1 border rounded', function() {
                    state.page = Math.max(1, state.page - 1);
                    loadLibrary();
                });
                var next = btn('Next', 'px-3 py-1 border rounded', function() {
                    state.page = Math.min(lp, state.page + 1);
                    loadLibrary();
                });
                if (cp <= 1) prev.disabled = true;
                if (cp >= lp) next.disabled = true;
                controls.appendChild(prev);
                controls.appendChild(next);
                paging.appendChild(label);
                paging.appendChild(controls);
            }

            function renderList(data) {
                while (listWrap.firstChild) listWrap.removeChild(listWrap.firstChild);
                if (state.view === 'grid') {
                    var grid = h('div', {
                        class: 'p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3'
                    });
                    (data || []).forEach(function(it) {
                        grid.appendChild(buildThumb(it));
                    });
                    listWrap.appendChild(grid);
                } else {
                    var header = h('div', {
                        class: 'grid grid-cols-12 gap-2 text-xs font-medium p-2 border-b bg-gray-50'
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
                    listWrap.appendChild(header);
                    (data || []).forEach(function(it) {
                        listWrap.appendChild(buildRow(it));
                    });
                }
                renderSelectionHighlights();
            }

            function renderSelectionHighlights() {
                var nodes = listWrap.querySelectorAll('[data-media-id]');
                Array.prototype.forEach.call(nodes, function(el) {
                    var id = +el.getAttribute('data-media-id');
                    var on = state.selected.has(id);
                    el.classList.toggle('ring-2', on);
                    el.classList.toggle('ring-blue-400', on);
                });
                cnt.textContent = state.selected.size + ' selected';
            }

            function renderSidebar() {
                while (side.firstChild) side.removeChild(side.firstChild);
                if (state.selected.size !== 1) {
                    side.appendChild(h('div', {
                            class: 'p-4 text-sm text-gray-500'
                        }, state.selected.size ? 'Multiple items selected.' :
                        'Select an item to see details.'));
                    return;
                }
                var id = state.selected.keys().next().value;
                fetchShow(id).then(function(item) {
                    side.appendChild(buildPreviewSidebar(item));
                });
            }

            // Expose inner helpers
            renderModal.renderList = renderList;
            renderModal.renderPaging = renderPaging;
            renderModal.renderSidebar = renderSidebar;
            renderModal.listWrap = listWrap;
            renderModal.prog = prog;
        }

        function switchTab(tab) {
            state.tab = tab;
            var up = modal.querySelector('#mb-upload');
            var lb = modal.querySelector('#mb-library');
            if (up && lb) {
                up.classList.toggle('hidden', tab !== 'upload');
                lb.classList.toggle('hidden', tab !== 'library');
            }
            if (tab === 'library') loadLibrary();
        }

        function toggleSelect(item) {
            if (!state.multiple) state.selected.clear();
            if (state.selected.has(item.id)) state.selected.delete(item.id);
            else state.selected.set(item.id, item);
            renderModal.renderSelectionHighlights && renderModal.renderSelectionHighlights();
            renderModal.renderSidebar && renderModal.renderSidebar();
        }

        function loadLibrary() {
            var lw = renderModal.listWrap;
            while (lw.firstChild) lw.removeChild(lw.firstChild);
            lw.appendChild(h('div', {
                class: 'p-4 text-sm text-gray-500'
            }, 'Loading...'));

            fetchList().then(function(res) {
                renderModal.renderList(res && res.data);
                renderModal.renderPaging(res && res.meta);
                renderModal.renderSidebar();
            });
        }

        function handleFiles(fileList) {
            var prog = renderModal.prog;
            while (prog.firstChild) prog.removeChild(prog.firstChild);
            uploadFiles(fileList).then(function(res) {
                var arr = (res && res.uploaded) || [];
                arr.forEach(function(it) {
                    var line = h('div', {
                        class: 'text-xs'
                    }, 'Uploaded: ' + (it.filename || it.url || ('#' + it.id)));
                    prog.appendChild(line);
                    state.selected.set(it.id, it);
                });
                switchTab('library');
                loadLibrary();
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

        function close() {
            if (modal) modal.remove();
            if (backdrop) backdrop.remove();
            modal = null;
            backdrop = null;
            state = null;
        }

        // Public entry
        window.openMediaBrowser = function(opts) {
            if (modal) try {
                close();
            } catch (e) {}
            state = defState(opts || {});
            renderModal();
            if (state.tab === 'library') loadLibrary();
            return new Promise(function(res) {
                resolvePromise = res;
            });
        };
    })();
</script>
