<?php
if (!defined('ABSPATH')) exit; // Security check
?>
<div class="tcat-dashboard-overview">
    <h2><span class="dashicons dashicons-chart-bar"></span> Dashboard Overview</h2>

    <!-- Job Stats -->
    <div class="tcat-overview-section">
        <h3><span class="dashicons dashicons-portfolio"></span> Job Stats</h3>
        <div class="tcat-card-grid">
            <div class="tcat-card blue">
                <h4>Total Active Jobs</h4>
                <p class="stat-number">12</p>
            </div>
            <div class="tcat-card orange">
                <h4>Jobs Expiring Soon</h4>
                <p class="stat-number">3</p>
            </div>
            <div class="tcat-card green">
                <h4>Teaching Roles</h4>
                <p class="stat-number">8</p>
            </div>
            <div class="tcat-card purple">
                <h4>Non-Teaching Roles</h4>
                <p class="stat-number">4</p>
            </div>
        </div>
    </div>

    <!-- Application Stats -->
    <div class="tcat-overview-section">
        <h3><span class="dashicons dashicons-groups"></span> Application Stats</h3>
        <div class="tcat-card-grid">
            <div class="tcat-card blue">
                <h4>Total Applications</h4>
                <p class="stat-number">54</p>
            </div>
            <div class="tcat-card grey">
                <h4>New</h4>
                <span class="badge badge-new">10</span>
            </div>
            <div class="tcat-card yellow">
                <h4>Shortlisted</h4>
                <span class="badge badge-shortlisted">5</span>
            </div>
            <div class="tcat-card red">
                <h4>Rejected</h4>
                <span class="badge badge-rejected">8</span>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="tcat-overview-section">
        <h3><span class="dashicons dashicons-update"></span> Recent Activity</h3>
        <ul class="activity-feed">
            <li><strong>Job Posted:</strong> Maths Teacher <span class="time">2 hours ago</span></li>
            <li><strong>Application Received:</strong> John Smith for English Teacher <span class="time">5 hours ago</span></li>
            <li><strong>Job Expiring Soon:</strong> Science Teacher <span class="time">1 day left</span></li>
        </ul>
    </div>

    <!-- Alerts -->
    <div class="tcat-overview-section">
        <h3><span class="dashicons dashicons-warning"></span> Alerts & Reminders</h3>
        <ul class="alerts-list">
            <li class="alert alert-warning">2 Jobs need review</li>
            <li class="alert alert-danger">5 Applications pending decision</li>
        </ul>
    </div>
</div>