<?php
/**
 * Plugin Name: WP Miniplan
 * Plugin URI: https://sedrubal.github.io/wp-miniplan
 * Description: Displays a "Miniplan" on Wordpress sites and let privileged people edit it. Use the <code>[miniplan id="x"]</code> tag.
 * Version: 0.0.1
 * Author: sedrubal
 * Author URI: https://github.com/sedrubal
 * Network: false
 * License: CC BY 4.0
 */

defined('ABSPATH') or die("[!] This script must be executed by a wordpress instance!\r\n");

global $miniplan_db_version;
$miniplan_db_version = '0.0.4';

require_once( 'views.php' );
require_once( 'db.php' );

//default privileged roles for editing miniplans
$miniplan_default_privileged_roles = array('administrator', 'editor', 'author');

/**
 * install...
 */

function miniplan_install() {
	miniplan_install_db();
	miniplan_add_new( 1, 'Demo Plan', 'Alle Ministranten', 'Max Mustermann', 'Bearbeite diesen Plan oder l&ouml;sche ihn', current_time( 'Y-m-d' ), date('Y-m-d', strtotime("+1 week")));
	set_option('miniplan_privileged_roles', $miniplan_default_privileged_roles);
}

function miniplan_update_db_check() {
    global $miniplan_db_version;
    if ( get_site_option( 'miniplan_db_version' ) != $miniplan_db_version ) {
        miniplan_install_db();
    }
}

/**
 * uninstall
 */
function miniplan_uninstall() {
	miniplan_drop_db();
	delete_option('miniplan_privileged_roles');
}

/**
 *  Register hooks etc.
 */

/**
 * registers query variables (get, post) to wordpress
 * @param $vars: an array containing all previous registered query vars
 * @return array: an array containing all query vars needed by this plugin and all previous registered vars
 */
function add_miniplan_query_vars_filter( $vars ){
  $vars[] = "miniplan";
  $vars[] = "miniplan_admin_action";
  $vars[] = "mpl_title";
  $vars[] = "mpl_text";
  $vars[] = "mpl_attendance";
  $vars[] = "mpl_notification";
  $vars[] = "mpl_beginning";
  $vars[] = "mpl_until";
  $vars[] = "mpl_submit";
  return $vars;
}
add_filter( 'query_vars', 'add_miniplan_query_vars_filter' );

// install and uninstall
register_activation_hook( __FILE__, 'miniplan_install' );
add_action( 'plugins_loaded', 'miniplan_update_db_check' );
register_uninstall_hook(__FILE__, 'miniplan_uninstall');

// SHORTCODES ( [miniplan id="x"] )
add_shortcode( 'miniplan', 'print_miniplan' );

// Register style sheet and scripts for datepicker.
// TODO: remove google spying
function register_datepicker() {
	wp_enqueue_script('jquery-ui-datepicker');
	global $wp_scripts;
	wp_enqueue_script('jquery-ui-tabs');
	$ui = $wp_scripts->query('jquery-ui-core');
	$protocol = is_ssl() ? 'https' : 'http';
	$url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
	wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
}
add_action( 'wp_enqueue_scripts', 'register_datepicker' );

// admin menu in wordpress admin section
add_action('admin_menu', 'miniplan_menu_pages');
/**
 * adds a link to plugin settings in the plugin section
 * @param array $links: some links (wordpress foo)
 */
function miniplan_settings_link($links) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=miniplan-admin-settings">Einstellungen</a>';
	array_unshift($links, $settings_link);
	return $links;
}
// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'miniplan_settings_link' );
