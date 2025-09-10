document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-mobile-toggle]");
    if (!btn) return;
    const nav = document.querySelector("[data-mobile-nav]");
    if (!nav) return;
    nav.classList.toggle("cf-open");
});
