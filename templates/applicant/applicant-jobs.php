<?php
if (!defined('ABSPATH')) exit;

// Get all terms for filters
$job_types = get_terms(['taxonomy' => 'job_type', 'hide_empty' => true]);
$job_categories = get_terms(['taxonomy' => 'job_category', 'hide_empty' => true]);
$schools = get_terms(['taxonomy' => 'school', 'hide_empty' => true]);

// Get filter values from URL
$filter_type = sanitize_text_field($_GET['filter_job_type'] ?? '');
$filter_category = sanitize_text_field($_GET['filter_job_category'] ?? '');
$filter_school = sanitize_text_field($_GET['filter_school'] ?? '');

// WP_Query args with filters
$tax_query = [];
if ($filter_type) {
    $tax_query[] = ['taxonomy' => 'job_type', 'field' => 'slug', 'terms' => $filter_type];
}
if ($filter_category) {
    $tax_query[] = ['taxonomy' => 'job_category', 'field' => 'slug', 'terms' => $filter_category];
}
if ($filter_school) {
    $tax_query[] = ['taxonomy' => 'school', 'field' => 'slug', 'terms' => $filter_school];
}

$args = [
    'post_type'      => 'tcat_job',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
];

if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}

$jobs = new WP_Query($args);
?>

<div class="tcat-applicant-jobs">

    <h2><span class="dashicons dashicons-portfolio"></span> Available Jobs</h2>

    <!-- Filter Form -->
    <form method="get" class="tcat-job-filters" style="margin-bottom:15px;">
        <select name="filter_job_type">
            <option value="">All Types</option>
            <?php foreach ($job_types as $type): ?>
                <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($filter_type, $type->slug); ?>>
                    <?php echo esc_html($type->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_job_category">
            <option value="">All Categories</option>
            <?php foreach ($job_categories as $category): ?>
                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($filter_category, $category->slug); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_school">
            <option value="">All Schools</option>
            <?php foreach ($schools as $school): ?>
                <option value="<?php echo esc_attr($school->slug); ?>" <?php selected($filter_school, $school->slug); ?>>
                    <?php echo esc_html($school->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

    </form>

    <!-- Job List -->
    <?php if ($jobs->have_posts()): ?>
        <div class="tcat-job-list">
            <?php while ($jobs->have_posts()): $jobs->the_post(); 
                $job_id = get_the_ID();
                $salary = get_post_meta($job_id, '_tcat_salary', true);
                $contract_type = get_post_meta($job_id, '_tcat_contract_type', true);
                $closing_date = get_post_meta($job_id, '_tcat_closing_date', true);
                $job_type_terms = get_the_terms($job_id, 'job_type');
                $school_terms = get_the_terms($job_id, 'school');
            ?>
                <div class="tcat-job-card" data-job-id="<?php echo esc_attr($job_id); ?>">
                    <button class="tcat-save-job-btn <?php echo in_array($job_id, (array)get_user_meta(get_current_user_id(), '_tcat_saved_jobs', true)) ? 'saved' : ''; ?>" data-job-id="<?php echo esc_attr($job_id); ?>">
                        <span class="dashicons dashicons-heart"></span>
                    </button>
                    <h3><?php the_title(); ?></h3>
                    <p><strong>School:</strong> <?php echo !empty($school_terms) ? esc_html($school_terms[0]->name) : 'N/A'; ?></p>
                    <p><strong>Type:</strong> <?php echo !empty($job_type_terms) ? esc_html($job_type_terms[0]->name) : 'N/A'; ?></p>
                    <p><strong>Closing Date:</strong> <?php echo $closing_date ? esc_html(date('d M Y', strtotime($closing_date))) : 'N/A'; ?></p>
                    <p><strong>Salary:</strong> <?php echo esc_html($salary ?: 'N/A'); ?></p>
                    <p><strong>Contract Type:</strong> <?php echo esc_html($contract_type ?: 'N/A'); ?></p>

                    <div class="tcat-job-actions">
                        <button class="tcat-apply-btn" data-job-id="<?php echo esc_attr($job_id); ?>">
                            Apply Now
                        </button>
                        
                        <button class="tcat-job-description-btn" data-job-id="<?php echo esc_attr($job_id); ?>">
                            Job Details
                        </button>
                    </div>
                </div>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <div id="tcat-job-preview-panel" style="display:none; border:1px solid #ccc; padding:20px; margin-top:20px; background:#fff; border-radius:8px;">
        <button id="close-job-preview" 
                style="float:right; background:#911A24; color:#fff; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:bold;">
            Close
        </button>
        <div id="job-preview-content"></div>
    </div>

    <?php else: ?>
        <p>No jobs available right now.</p>
    <?php endif; ?>
</div>
