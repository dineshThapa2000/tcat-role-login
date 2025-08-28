(function($){
        function loadSection(section) {
            $('#tcat-applicant-content').html('<p>Loading...</p>');
            $.get(tcat_applicant_dashboard.ajaxurl,{
                action: 'load_applicant_section',
                section: section
            }, function(response){
                $('#tcat-applicant-content').html(response);
            });
        }

        $(document).on('click', '.tcat-sidebar a', function(e){
            e.preventDefault();
            loadSection($(this).data('section'));
        });

        loadSection('jobs');

 })(jQuery);