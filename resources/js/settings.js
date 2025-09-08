// Alpine component for AJAX settings save
window.settingsForm = (cfg) => ({
    csrf: cfg.csrf,
    saveUrl: cfg.saveUrl,
    restoreUrl: cfg.restoreUrl,
    saving: false,
    errors: {},

    serialize(form) {
        // Converts nested input names like general[site_title] into { general: { site_title: ... } }
        const data = {};
        const fdata = new FormData(form);

        const assign = (obj, path, val) => {
            const last = path.pop();
            let ref = obj;
            for (const key of path) {
                if (!(key in ref)) ref[key] = {};
                ref = ref[key];
            }
            ref[last] = val;
        };

        for (const [name, value] of fdata.entries()) {
            // checkbox unchecked => not present; that's fine
            const tokens = name.replace(/\]/g, "").split("[");
            assign(data, tokens, value);
        }

        // normalize checkboxes to booleans based on presence in form
        form.querySelectorAll("input[type=checkbox]").forEach((chk) => {
            const name = chk.name;
            if (!name) return;
            const tokens = name.replace(/\]/g, "").split("[");
            // walk/create path
            let ref = data,
                i = 0;
            for (; i < tokens.length - 1; i++) {
                ref = ref[tokens[i]] ?? (ref[tokens[i]] = {});
            }
            const last = tokens[i];
            if (!(last in ref)) ref[last] = false; // unchecked => false
            else
                ref[last] =
                    ref[last] === "1" ||
                    ref[last] === "on" ||
                    ref[last] === "true";
        });

        return data;
    },

    async submit() {
        this.saving = true;
        this.errors = {};
        const form = this.$refs.form;
        const payload = this.serialize(form);

        try {
            const res = await fetch(this.saveUrl, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": this.csrf,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const text = await res.text();
            const ct = res.headers.get("content-type") || "";
            const data = ct.includes("application/json")
                ? JSON.parse(text)
                : { ok: res.ok };

            if (!res.ok || data.ok === false) {
                if (data.errors) this.errors = this.flattenErrors(data.errors);
                else alert(text);
                return;
            }
            // success toast
            alert("Settings saved.");
        } catch (e) {
            console.error(e);
            alert("Failed to save settings.");
        } finally {
            this.saving = false;
        }
    },

    flattenErrors(errs) {
        // errs like { 'site_title': ['The site title field is required.'], 'post_via_email.host': ['...'] }
        const out = {};
        for (const k in errs)
            out[k.split(".").slice(-1)[0]] = errs[k][0] || "Invalid.";
        return out;
    },
});
