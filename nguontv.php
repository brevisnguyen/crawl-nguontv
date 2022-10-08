<?php

/*
* @wordpress-plugin
* Plugin Name: NguonTV Crawler
* Plugin URI: https://nguon.tv
* Description: Thu thập phim từ NguonTV - Tương thích theme HaLimMovie
* Version: 2.0.1
* Requires PHP: 7.4^
* Author: Brevis Nguyen
* Author URI: https://github.com/brevis-ng
*/

// Protect plugins from direct access. If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die('Hành động chưa được xác thực!');
}

/**
 * The unique identifier of this plugin.
 */
set_time_limit(0);

define('NGUONTV_NAME', 'nguontv-crawler');
define('NGUONTV_VERSION', '2.0.1');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_nguontv() {
    // Code
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_nguontv() {
    // Code
}

register_activation_hook( __FILE__, 'activate_nguontv' );
register_deactivation_hook( __FILE__, 'deactivate_nguontv' );

/**
 * Provide a public-facing view for the plugin
 */
function nguon_crawler_add_menu() {
    add_menu_page(
        __('NguonTV Crawler Tools', 'textdomain'),
        'NguonTV Crawl',
        'manage_options',
        'nguontv-crawler-tools',
        'nguon_crawler_page_menu',
        'dashicons-buddicons-replies',
        2
    );
}

/**
 * Include the following files that make up the plugin
 */
function nguon_crawler_page_menu() {
    require_once plugin_dir_path(__FILE__) . 'public/partials/nguontv-crawler-view.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 * 
 */
require_once plugin_dir_path( __FILE__ ) . 'public/nguontv-crawler.php';
function run_nguon_crawler() {
    add_action('admin_menu', 'nguon_crawler_add_menu');

    $plugin_admin = new Nguon_Movies_Crawler( NGUONTV_NAME, NGUONTV_VERSION );
    add_action('in_admin_header', array($plugin_admin, 'enqueue_nguon_scripts'));
    add_action('in_admin_header', array($plugin_admin, 'enqueue_nguon_styles'));

    add_action('wp_ajax_nguon_crawler_api', array($plugin_admin, 'nguon_crawler_api'));
    add_action('wp_ajax_nguon_get_movies_page', array($plugin_admin, 'nguon_get_movies_page'));
    add_action('wp_ajax_nguon_crawl_by_id', array($plugin_admin, 'nguon_crawl_by_id'));
}
run_nguon_crawler();