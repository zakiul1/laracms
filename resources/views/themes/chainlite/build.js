const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const dist = path.resolve(__dirname, "assets/dist");
if (!fs.existsSync(dist)) fs.mkdirSync(dist, { recursive: true });

const manifest = {};
function hashRename(basename) {
    const src = path.join(dist, basename);
    if (!fs.existsSync(src)) return;
    const buf = fs.readFileSync(src);
    const hash = crypto
        .createHash("sha256")
        .update(buf)
        .digest("hex")
        .slice(0, 10);
    const ext = path.extname(basename);
    const name = path.basename(basename, ext);
    const out = `${name}.${hash}${ext}`;
    fs.copyFileSync(src, path.join(dist, out));
    manifest[basename] = out;
}
hashRename("theme.css");
hashRename("theme.js"); // optional
fs.writeFileSync(
    path.join(dist, "manifest.json"),
    JSON.stringify(manifest, null, 2)
);
console.log("Manifest:", manifest);
