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

# <editor-fold desc="require, etc.">
defined('ABSPATH') or die("[!] This script must be executed by a wordpress instance!\r\n");

define('MINIPLAN_ABSPATH', __DIR__);

require_once( 'views/views.php' );
require_once( 'views/display_miniplan.php' );
require_once( 'views/admin_settings.php' );
require_once( 'db.php' );
# </editor-fold>

# <editor-fold desc="default settings">
// default privileged roles for editing miniplans
$miniplan_default_privileged_roles = array('administrator', 'editor', 'author');
# </editor-fold>

# <editor-fold desc="Register things to wordpress">

# <editor-fold desc="functions to register things">

/**
 * registers query variables (get, post) to wordpress
 * @param $vars: an array containing all previous registered query vars
 * @return array: an array containing all query vars needed by this plugin and all previous registered vars
 */
function add_miniplan_query_vars_filter( $vars ){
    $vars[] = "miniplan";
    $vars[] = "miniplan_admin_action";
    $vars[] = "mpl_text";
    $vars[] = "mpl_attendance";
    $vars[] = "mpl_notification";
    $vars[] = "mpl_beginning";
    $vars[] = "mpl_until";
    $vars[] = "mpl_submit";
    return $vars;
}

/**
 * Register style sheet and scripts for datepicker.
 * TODO: remove google spying
 */
function register_datepicker() {
    /**	wp_enqueue_script('jquery-min', plugins_url('/js/jquery.min.js',  __FILE__ ));
    wp_enqueue_script('jquery-ui-min', plugins_url('/js/jquery-ui.min.js',  __FILE__ ));
    wp_enqueue_style('jquery-ui-min', plugins_url('/css/jquery-ui.min.css',  __FILE__ ));
     **/

    wp_enqueue_script('jquery-ui-datepicker');
    global $wp_scripts;
    wp_enqueue_script('jquery-ui-tabs');
    $ui = $wp_scripts->query('jquery-ui-core');
    $protocol = is_ssl() ? 'https' : 'http';
    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
}

/**
 * adds a link to plugin settings in the plugin section
 * @param array $links : some links (wordpress foo)
 * @return array all links set until yet
 */
function miniplan_settings_link($links) {
    $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=miniplan-admin-settings">' . translate( 'Einstellungen' ) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

# <editor-fold desc="install, update">
/**
 * install...
 */
function miniplan_install() {
    miniplan_db::install();
    miniplan_db::add_new_miniplan( 1, 'Alle Ministranten', 'Max Mustermann', 'Bearbeite diesen Plan oder l&ouml;sche ihn', DateTime::createFromFormat('dd.mm.Y', date('dd.mm.Y')), DateTime::createFromFormat('dd.mm.Y', date('dd.mm.Y'))->add(date_interval_create_from_date_string('6 days')));
    global $miniplan_default_privileged_roles;
    add_option('miniplan_privileged_roles', $miniplan_default_privileged_roles);
}

/**
 * uninstall
 */
function miniplan_uninstall() {
    miniplan_db::drop_tables();
    delete_option('miniplan_privileged_roles');
}
# </editor-fold>

# </editor-fold>

# <editor-fold desc="register hooks">
// install and uninstall
register_activation_hook( __FILE__, 'miniplan_install' );
register_uninstall_hook(__FILE__, 'miniplan_uninstall');
# </editor-fold>

# <editor-fold desc="add actions, filters, shortcodes">

add_action( 'plugins_loaded', array( 'db', 'check_update' ) );

add_action( 'wp_enqueue_scripts', 'register_datepicker' );

// admin menu in wordpress admin section
add_action('admin_menu', 'miniplan_menu_pages');

add_filter( 'query_vars', 'add_miniplan_query_vars_filter' );

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'miniplan_settings_link' );

// SHORTCODES ( [miniplan id="x"] )
add_shortcode( 'miniplan', 'display_miniplan');

# </editor-fold>

# </editor-fold>