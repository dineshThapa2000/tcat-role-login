jQuery(document).ready(function ($) {
    // Sidebar menu click
    $(document).on('click', '.tcat-sidebar a', function (e) {
        e.preventDefault();
        const section = $(this).data('section');

        $('#tcat-admin-content').html('<p>Loading...</p>');

        $.get(
            tcat_dashboard.ajaxurl,
            {
                action: 'load_admin_section',
                section: section
            },
            function (response) {
                $('#tcat-admin-content').html(response);

                // If jobs section, initialize admin jobs functionality
                if (section === 'jobs' && typeof initAdminJobs === 'function') {
                    initAdminJobs();
                }
            }
        );
    });
});
