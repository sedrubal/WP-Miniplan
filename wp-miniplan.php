<?php
/**
 * Plugin Name: WP Miniplan
 * Plugin URI: https://sedrubal.github.io/wp-miniplan
 * Description: Displays a "Miniplan" on Wordpress sites and let privileged people edit it. Use the [miniplan id='x'] tag.
 * Version: 0.0.1
 * Author: sedrubal
 * Author URI: https://github.com/sedrubal
 * Network: false
 * License: CC BY 4.0
 */

defined('ABSPATH') or die("[!] This scipt must be executed by a wordpress instance!\r\n");

global $miniplan_db_version;
$miniplan_db_version = '0.0.3';

require_once( 'views.php' );

/**
 * install...
 */

function miniplan_install() {

        global $wpdb;
        global $miniplan_db_version;

        $table_name = $wpdb->prefix . 'miniplan';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                feedid tinyint DEFAULT '1' NOT NULL,
		beginning datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		until datetime DEFAULT '0000-00-07 00:00:00' NOT NULL,
                title tinytext NOT NULL,
                text text NOT NULL,
                UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option( 'miniplan_db_version', $miniplan_db_version );
}

function miniplan_install_data() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'miniplan';

        $wpdb->insert($table_name,
                array(
			'feedid' => '1',
                        'beginning' => current_time( 'Y-m-d' ),
                        'until' => date('Y-m-d', strtotime("+1 week")),
                        'title' => 'Demo Plan',
                        'text' => 'Alle Ministranten',
                )
        );
}

function miniplan_update_db_check() {
    global $miniplan_db_version;
    if ( get_site_option( 'miniplan_db_version' ) != $miniplan_db_version ) {
        miniplan_install();
    }
}

/**
 *  Register hooks etc.
 */

//GET Parameter ( ?miniplan=x )
function add_miniplan_query_vars_filter( $vars ){
  $vars[] = "miniplan";
  $vars[] = "miniplan_admin_action";
  $vars[] = "mpl_title";
  $vars[] = "mpl_text";
  $vars[] = "mpl_beginning";
  $vars[] = "mpl_until";
  $vars[] = "mpl_submit";
  return $vars;
}
add_filter( 'query_vars', 'add_miniplan_query_vars_filter' );

//DB / [wp_miniplan] )
register_activation_hook( __FILE__, 'miniplan_install' );
register_activation_hook( __FILE__, 'miniplan_install_data' );
add_action( 'plugins_loaded', 'miniplan_update_db_check' );

//SHORTCODES ( [miniplan id="x"] )
add_shortcode( 'miniplan', 'print_miniplan' );

// Register style sheet and scripts for datepicker.
//TODO: remove google spying
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
