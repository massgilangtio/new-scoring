            </div><!-- /.page -->
        </div>
        <!-- END #content -->

        <!-- BEGIN scroll-top-btn -->
        <a href="javascript:;" class="btn btn-icon btn-circle btn-theme btn-scroll-to-top" data-toggle="scroll-to-top" aria-label="Scroll to top">
            <i class="fa fa-angle-up"></i>
        </a>
        <!-- END scroll-top-btn -->
    </div>
    <!-- END #app -->

<script>
    /* Keyboard shortcut: Ctrl+K → focus search (functionality preserved) */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            var input = document.querySelector('.app-header form[name="search"] input');
            if (!input) return;
            e.preventDefault();
            input.focus();
        }
    });
</script>
</body>
</html>
