        </main>
    </div><!-- .app-workspace -->
</div><!-- .app-frame -->

<script>
    /* ============================================================
       Sidebar Toggle / Collapse
       ============================================================ */
    (function () {
        // Restore preference on desktop
        if (localStorage.getItem('sidebar_collapsed') === '1' && window.innerWidth >= 992) {
            document.body.classList.add('sidebar-collapsed');
        }

        var collapseBtn = document.getElementById('sidebarCollapseBtn');

        function toggleSidebarState(e) {
            e.preventDefault();
            if (window.innerWidth < 992) {
                var offcanvasEl = document.getElementById('appSidebar');
                if (offcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    var bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
                    bsOffcanvas.toggle();
                }
            } else {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
            }
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', toggleSidebarState);
        }
    })();

    /* ============================================================
       Keyboard shortcut: Ctrl+K → focus search
       ============================================================ */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            var input = document.querySelector('.topbar-search input, .top-search input');
            if (!input) return;
            e.preventDefault();
            input.focus();
        }
    });

    /* ============================================================
       User chip dropdown — close when clicking outside
       ============================================================ */
    document.addEventListener('click', function (e) {
        var chip = document.querySelector('.user-chip');
        if (chip && !chip.contains(e.target)) {
            chip.removeAttribute('open');
        }
    });
</script>
</body>
</html>
