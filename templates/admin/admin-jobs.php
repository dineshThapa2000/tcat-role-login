<?php
if (!defined('ABSPATH')) exit;

$args = [
    'post_type'      => 'tcat_job',
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'draft'],
    'orderby'        => 'date',
    'order'          => 'DESC',
];

$jobs = get_posts($args);
$today = date('Y-m-d');

// Get unique Job Types & Schools for dropdowns
$job_types = get_terms(['taxonomy' => 'job_type', 'hide_empty' => false]);
$schools   = get_terms(['taxonomy' => 'school', 'hide_empty' => false]);

echo '<div class="tcat-admin-jobs-wrapper">';
echo '<h2 style="margin-bottom:15px;">Jobs Overview</h2>';

if ($jobs) {
    // Filter controls
    echo '<div class="tcat-job-filters" style="margin-bottom:15px; display:flex; gap:10px; align-items:center; flex-wrap: wrap;">';
    $filter_style = 'padding:8px 10px; border:1px solid #ccc; border-radius:5px; font-size:14px;';
    echo '<input type="text" id="filter-search" placeholder="Search Job Title..." style="' . $filter_style . ' width:200px;">';
    
    // Job Type Filter
    echo '<select id="filter-job-type" style="' . $filter_style . '">
            <option value="">All Types</option>';
    foreach ($job_types as $type) {
        echo '<option value="' . esc_attr($type->name) . '">' . esc_html($type->name) . '</option>';
    }
    echo '</select>';

    // School Filter
    echo '<select id="filter-school" style="' . $filter_style . '">
            <option value="">All Schools</option>';
    foreach ($schools as $school) {
        echo '<option value="' . esc_attr($school->name) . '">' . esc_html($school->name) . '</option>';
    }
    echo '</select>';

    // Status Filter
    echo '<select id="filter-status" style="' . $filter_style . '">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Draft">Draft</option>
            <option value="Expired">Expired</option>
          </select>';

    echo '</div>';

    // Table start
    echo '<table id="tcat-jobs-table" style="width:100%; border-collapse: collapse; margin-top:10px; font-family: Arial, sans-serif; font-size:14px;">';
    echo '<thead>
            <tr style="background:#003848; color:white; text-align:left;">
                <th style="padding:8px; border:1px solid #ddd;">Job Title</th>
                <th style="padding:8px; border:1px solid #ddd;">Type</th>
                <th style="padding:8px; border:1px solid #ddd;">School</th>
                <th style="padding:8px; border:1px solid #ddd;">Closing Date</th>
                <th style="padding:8px; border:1px solid #ddd;">Status</th>
                <th style="padding:8px; border:1px solid #ddd;">Preview</th>
            </tr>
          </thead>';
    echo '<tbody>';

    $row_count = 0;
    foreach ($jobs as $job) {
        $job_id = $job->ID;

        $type_terms = wp_get_post_terms($job_id, 'job_type', ['fields' => 'names']);
        // Join types with pipe "|" for JS filtering
        $type_attr  = !empty($type_terms) ? implode('|', $type_terms) : '';
        $type_display = !empty($type_terms) ? implode(', ', $type_terms) : '';

        $school_terms = wp_get_post_terms($job_id, 'school', ['fields' => 'names']);
        $school = !empty($school_terms) ? implode(', ', $school_terms) : '';

        $closing_date_raw = get_post_meta($job_id, '_tcat_closing_date', true);
        $closing_date_display = '—';
        $date_style = '';
        if (!empty($closing_date_raw)) {
            $closing_timestamp = strtotime($closing_date_raw);
            $closing_date_display = date('d M Y', $closing_timestamp);
            $days_left = (strtotime($closing_date_raw) - strtotime($today)) / 86400;
            if ($days_left >= 0 && $days_left <= 7) {
                $date_style = 'color:darkorange; font-weight:bold;';
            }
        }

        if (!empty($closing_date_raw) && strtotime($closing_date_raw) < strtotime($today)) {
            $status_text = 'Expired';
            $status_display = '<span style="color:red; font-weight:bold;">Expired</span>';
        } elseif (get_post_status($job_id) === 'draft') {
            $status_text = 'Draft';
            $status_display = '<span style="color:orange; font-weight:bold;">Draft</span>';
        } else {
            $status_text = 'Active';
            $status_display = '<span style="color:green; font-weight:bold;">Active</span>';
        }

        $row_bg = ($row_count % 2 === 0) ? '#ffffff' : '#f9f9f9';
        $row_count++;

        $edit_link = admin_url('post.php?post=' . $job_id . '&action=edit');

        echo '<tr style="background:' . $row_bg . '"
                data-job-type="' . esc_attr($type_attr) . '"
                data-school="' . esc_attr($school) . '"
                data-status="' . esc_attr($status_text) . '">';
        echo '<td style="padding:8px; border:1px solid #ddd;">
                <a href="' . esc_url($edit_link) . '" style="color:#0073aa; text-decoration:none;">
                    ' . esc_html(get_the_title($job_id)) . '
                </a>
              </td>';
        echo '<td style="padding:8px; border:1px solid #ddd;">' . esc_html($type_display) . '</td>';
        echo '<td style="padding:8px; border:1px solid #ddd;">' . esc_html($school) . '</td>';
        echo '<td style="padding:8px; border:1px solid #ddd;' . $date_style . '">' . esc_html($closing_date_display) . '</td>';
        echo '<td style="padding:8px; border:1px solid #ddd;">' . $status_display . '</td>';

        // Preview icon column
        echo '<td style="padding:8px; border:1px solid #ddd; text-align:center;">
                <button class="preview-job-btn" data-job-id="' . esc_attr($job_id) . '" 
                        style="background:none; border:none; cursor:pointer; font-size:18px;">👁️</button>
              </td>';

        echo '</tr>';
    }

    echo '</tbody></table>';

    // Job preview panel (hidden by default)
    echo '<div id="tcat-job-preview-panel" style="display:none; border:1px solid #ccc; padding:20px; margin-top:20px; background:#f5f5f5; border-radius:5px;">
            <button id="close-job-preview" 
                    style="float:right; background:#003848; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold;">
                Close
            </button>
            <div id="job-preview-content"></div>
          </div>';

    echo '<div id="tcat-pagination" style="margin-top:15px; text-align:center;"></div>';

} else {
    echo '<p>No jobs found.</p>';
}

echo '</div>';
