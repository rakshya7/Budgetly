/*
 * Mobile sidebar drawer.
 * On phones the sidebar is off-canvas and opened from the top bar.
 */

(function () {

    var toggle = document.getElementById("sidebarToggle");
    var overlay = document.getElementById("sidebarOverlay");
    var sidebar = document.getElementById("sidebar");

    if (!toggle || !overlay || !sidebar) {
        return;
    }

    function setOpen(open) {
        document.body.classList.toggle("sidebar-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    }

    toggle.addEventListener("click", function () {
        setOpen(!document.body.classList.contains("sidebar-open"));
    });

    overlay.addEventListener("click", function () {
        setOpen(false);
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            setOpen(false);
        }
    });

    sidebar.addEventListener("click", function (event) {
        if (event.target.closest("a")) {
            setOpen(false);
        }
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth >= 768) {
            setOpen(false);
        }
    });

})();
