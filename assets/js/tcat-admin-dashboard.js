jQuery(document).ready(function ($) {
    $(document).on('click', '.tcat-sidebar a', function (e) {
        e.preventDefault();
        const section = $(this).data('section');

        $('#tcat-admin-content').html('<p>Loading...</p>');

        $('#tcat-admin-content').load(
            tcat_dashboard.ajaxurl + '?action=load_admin_section&section=' + section,
            function () {
                if (section === 'jobs') {
                    $.getScript(tcat_dashboard.plugin_url + 'assets/js/admin-jobs.js')
                     .done(function() {
                        initAdminJobs(); // re-initialize filters/pagination on new table
                     });
                }
            }
        );
    });
});
