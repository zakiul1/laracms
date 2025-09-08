import Sortable from "sortablejs";

// ---- tiny helpers -----------------------------------------------------------
const assertUrl = (url) => {
    if (!url) throw new Error("Missing endpoint URL");
    return url;
};
// replace only a *trailing* "/0" placeholder
const withId = (tpl, id) => assertUrl(tpl).replace(/\/0$/, `/${id}`);

// unified fetch with CSRF + error surfacing
const api = async (url, method = "GET", body = null, csrf = null) => {
    const headers = { "X-Requested-With": "XMLHttpRequest" };
    if (csrf) headers["X-CSRF-TOKEN"] = csrf;
    if (body && method !== "GET") headers["Content-Type"] = "application/json";

    const res = await fetch(assertUrl(url), {
        method,
        headers,
        body: body && method !== "GET" ? JSON.stringify(body) : undefined,
    });

    const text = await res.text();
    if (!res.ok) {
        // try to show json error payloads in console
        try {
            console.error(JSON.parse(text));
        } catch {
            console.error(text);
        }
        throw new Error(text || `${method} ${url} failed (${res.status})`);
    }

    const ct = res.headers.get("content-type") || "";
    return ct.includes("application/json") ? JSON.parse(text) : text;
};

// ----------------------------------------------------------------------------
window.wpWidgets = (cfg) => ({
    endpoints: cfg.endpoints,
    csrf: cfg.csrf,

    tryJson(v) {
        try {
            return JSON.parse(v || "{}");
        } catch {
            return {};
        }
    },

    setupArea(el, areaId) {
        // Persist paging defaults on the container
        el.dataset.page = el.dataset.page || "1";
        el.dataset.per = el.dataset.per || "20";

        // Make the list sortable
        if (!el.__sortable) {
            el.__sortable = Sortable.create(el, {
                group: {
                    name: "widgets",
                    pull: true,
                    put: ["widgets", "palette"],
                },
                animation: 150,
                handle: ".cursor-move",
                onAdd: async (evt) => {
                    // ignore if dropped back in same list
                    if (evt.from === el) return;

                    const fromPalette =
                        evt.from.classList.contains("widget-palette");
                    if (fromPalette) {
                        const type = evt.item?.dataset?.type;
                        if (!type) return;

                        await api(
                            this.endpoints.store,
                            "POST",
                            {
                                widget_area_id: areaId,
                                type,
                                title: "",
                                settings: {},
                            },
                            this.csrf
                        );

                        // simplest correct refresh (keeps state consistent)
                        location.reload();
                        return;
                    }

                    // Moved from another area
                    const id = +evt.item.dataset.id;
                    if (!id) return;

                    await api(
                        withId(this.endpoints.update, id),
                        "PATCH",
                        {
                            widget_area_id: areaId,
                        },
                        this.csrf
                    );

                    await this.commitOrder(areaId);
                },
                onUpdate: async () => {
                    await this.commitOrder(areaId);
                },
            });
        }

        // Palette = clone-only source
        const palette = document.querySelector(".widget-palette");
        if (palette && !palette.__sortable) {
            palette.__sortable = Sortable.create(palette, {
                group: { name: "palette", pull: "clone", put: false },
                sort: false,
                animation: 150,
            });
        }
    },

    refreshSortable(el, areaId) {
        if (el.__sortable) el.__sortable.destroy();
        el.__sortable = null;
        this.setupArea(el, areaId);
    },

    async addFromSelect(areaId, type) {
        if (!type) return;
        await api(
            this.endpoints.store,
            "POST",
            {
                widget_area_id: areaId,
                type,
                title: "",
                settings: {},
            },
            this.csrf
        );
        location.reload();
    },

    async save(id, payload) {
        await api(
            withId(this.endpoints.update, id),
            "PATCH",
            payload,
            this.csrf
        );
    },

    async toggle(id) {
        await api(withId(this.endpoints.toggle, id), "POST", {}, this.csrf);
        location.reload();
    },

    async clone(id) {
        await api(withId(this.endpoints.clone, id), "POST", {}, this.csrf);
        location.reload();
    },

    async del(id) {
        if (!confirm("Delete this widget?")) return;
        await api(withId(this.endpoints.delete, id), "DELETE", null, this.csrf);

        // Remove from DOM without full reload (nice UX)
        const item = document.querySelector(`[data-id="${id}"]`);
        if (item) item.remove();
    },

    async commitOrder(areaId) {
        const list = document.querySelector(
            `.widgets-list[data-area="${areaId}"]`
        );
        if (!list) return;

        const order = Array.from(list.querySelectorAll("[data-id]")).map(
            (n) => +n.dataset.id
        );
        await api(
            this.endpoints.reorder,
            "POST",
            { area_id: areaId, order },
            this.csrf
        );
    },

    async loadMore(areaId) {
        const list = document.querySelector(
            `.widgets-list[data-area="${areaId}"]`
        );
        if (!list) return;

        const page = +(list.dataset.page || "1") + 1;
        const per = +(list.dataset.per || "20");

        const url = `${withId(
            this.endpoints.listArea,
            areaId
        )}?page=${page}&per=${per}`;
        const data = await api(url, "GET", null, this.csrf);

        // Expect { html: "...cards...", has_more: true/false }
        if (data?.html) {
            list.insertAdjacentHTML("beforeend", data.html);
            list.dataset.page = String(page);

            if (!data.has_more) {
                const btn = list
                    .closest(".p-3")
                    ?.querySelector(`[data-load="${areaId}"]`);
                if (btn) btn.classList.add("hidden");
            }
            this.refreshSortable(list, areaId);
        }
    },

    async deleteArea(areaId) {
        if (
            !confirm(
                "Delete this entire widget area? All its widgets will be removed."
            )
        )
            return;
        await api(
            withId(this.endpoints.deleteArea, areaId),
            "DELETE",
            null,
            this.csrf
        );
        location.reload();
    },
});
