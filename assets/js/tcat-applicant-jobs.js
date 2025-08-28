jQuery(document).ready(function($){

    function loadJobs(filters = {}) {
        $.ajax({
            url: tcat_applicant_dashboard.ajaxurl,
            type: 'GET',
            data: $.extend({ action: 'load_applicant_section', section: 'jobs' }, filters),
            success: function(response) {
                $('#tcat-applicant-content').html(response);
            }
        });
    }

    // Initial load
    loadJobs();

    // Filter change
    $(document).on('change', '.tcat-job-filters select', function () {
        const filters = {
            filter_job_type: $('select[name="filter_job_type"]').val(),
            filter_job_category: $('select[name="filter_job_category"]').val(),
            filter_school: $('select[name="filter_school"]').val(),
        };
        loadJobs(filters);
    });

    // Open job details panel
    jQuery(document).ready(function ($) {

    // When "Job Details" clicked
    $(document).on("click", ".tcat-job-description-btn", function (e) {
        e.preventDefault();
        const jobId = $(this).data("job-id");

        // Hide all cards
        $(".tcat-job-list").hide();

        // Show preview panel
        $("#tcat-job-preview-panel").show().addClass("loading");
        $("#job-preview-content").html("<p>Loading job details...</p>");

        // AJAX request to get details
        $.post(tcat_applicant_dashboard.ajaxurl, {
            action: "get_job_details",
            nonce: tcat_applicant_dashboard.nonce,
            job_id: jobId
        }, function (response) {
            if (response.success) {
                const job = response.data;
                $("#job-preview-content").html(`
                    <h2>${job.title}</h2>
                    <p><strong>Location:</strong> ${job.location || 'N/A'}</p>
                    <p><strong>Closing Date:</strong><span class='closing-date'> ${job.closing_date || 'N/A'}</span></p>
                    <div>
                        <button id="show-overview">Job Advert</button>
                        <button id="show-description">Job Description</button>
                    </div>
                    <div id="job-content-container">
                        <div id="overview-content">
                        <h2> Job Overview</h2>

                            <table>
                                <tr>
                                    <td>Salary:</td><td>${job.salary || 'N/A'}</td>
                                    <td>Hours:</td><td>${job.contract_hours || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td>Type:</td><td>${job.job_type || 'N/A'}</td>
                                    <td>Category:</td><td>${job.job_category || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td>Date Posted:</td><td>${job.date_posted || 'N/A'}</td>
                                    <td>Attachment:</td>
                                    <td>
                                        ${job.attachment 
                                            ? `<a href="${job.attachment}" target="_blank">Download</a>` 
                                            : 'N/A'}
                                    </td>
                                </tr>
                        
                            </table>
                            <p>${job.overview || 'No overview available.'}</p>
                        </div>
                        <div id="description-content" style="display:none;">
                        <h2> Job Description</h2>
                            <p>${job.job_description || 'No description provided.'}</p>
                        </div>
                    </div>
                ` );

                    document.getElementById("show-overview").addEventListener("click", () => {
                        document.getElementById("overview-content").style.display = "block";
                        document.getElementById("description-content").style.display = "none";
                    });
                    document.getElementById("show-description").addEventListener("click", () => {
                        document.getElementById("overview-content").style.display = "none";
                        document.getElementById("description-content").style.display = "block";
                    })
            } else {
                $("#job-preview-content").html("<p>Error loading job details.</p>");
            }
        });
    });

    // When "Close" clicked
    $(document).on("click", "#close-job-preview", function () {
        $("#tcat-job-preview-panel").hide();
        $(".tcat-job-list").show();
    });
    });
});
