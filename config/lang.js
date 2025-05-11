<script>
$(document).ready(function() {
    $('#ownersTable').DataTable({
        "dom": '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        "buttons": [
            <?php if (!$is_viewer): ?>
            'copy', 'csv', 'excel', 'pdf', 'print'
            <?php endif; ?>
        ],
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "<?php echo $lang === 'am' ? '//cdn.datatables.net/plug-ins/1.13.7/i18n/am.json' : '//cdn.datatables.net/plug-ins/1.13.7/i18n/en-GB.json'; ?>"
        }
    });

    // Auto-submit language switcher form on change
    $('#lang-switcher').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>