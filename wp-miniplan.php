<?php
/**
 * Plugin Name: WP Miniplan
 * Plugin URI: https://sedrubal.github.io/wp_miniplan
 * Description: Displays a "Miniplan" on Wordpress sites and let privileged people edit it. Use the [miniplan id='x'] tag.
 * Version: 0.0.1
 * Author: sedrubal
 * Author URI: https://github.com/sedrubal
 * Network: false
 * License: CC BY SA 4.0
 */

defined('ABSPATH') or die("[!] This scipt must be executed by a wordpress instance!\r\n");

global $miniplan_db_version;
$miniplan_db_version = '0.0.3';

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
 * Plugin functions
 */

function print_miniplan( $atts ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'miniplan';

	$date = get_query_var( "miniplan", "latest" );

	$dates = $wpdb->get_results( 'SELECT id,beginning,until FROM `' . $table_name . '` WHERE feedid = \'' . intval($atts["id"]) . '\' ORDER BY beginning DESC', OBJECT ); //get available plans from db (for dropdown)
	//build dropdown
	$ret = "<form action='' method='get'>
		Datum: <select onchange='this.form.submit()' name='miniplan' required>
			<option value='latest'" . ($date === "latest" ? " selected" : "") . ">aktueller Plan</option>";
	//options from db
	foreach ($dates as $d) {
		$ret .= "<option value='" . $d->id . "'" . (($date === $d->id) ? " selected>" : ">") . miniplan_date_format($d->beginning) . " - "  . miniplan_date_format($d->until) . "</option>";
	}
	$ret .="</select>
		<noscript><button type='submit'>aktualisieren</button></noscript>
	</form>";
	//query selected miniplan from db
	if ($date === "latest") {
		//get latest date until today
		$mostRecent= 0;
		$now = time();
		foreach($dates as $date){
			$curDate = strtotime($date->beginning);
			if ($curDate > $mostRecent && $curDate < $now) {
				$mostRecent = $curDate;
			}
		}

		$results = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '` WHERE feedid = \'' . intval($atts["id"]) . '\' AND DATE_FORMAT(beginning, "%Y-%m-%d") = DATE_FORMAT(\'' . date('Y-m-d', $mostRecent) . '\', "%Y-%m-%d") ORDER BY beginning DESC LIMIT 1', OBJECT );
	} else if (is_numeric($date)) {
		$results = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '` WHERE id = \'' . intval($date) . '\' ORDER BY beginning LIMIT 1', OBJECT );
	}
	if ($results == "" || empty($results)) {
		if (WP_DEBUG === true) { error_log("Something went wrong, while fetching the miniplan!"); }
		apply_filters('debug', "Something went wrong, while fetching the miniplan!");
		$ret .= "<div id='miniplan' class='error' style='border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#AA5555;'><h4>Der Miniplan kann aufgrund eines internen Fehlers nicht angezeigt werden.</h4></div>";
		return $ret;
	}
	//display miniplan
        $ret .= "<div id='miniplan' class='miniplan'><h3>" . $results[0]->title . " - gültig vom " . miniplan_date_format($results[0]->beginning) . " bis zum " . miniplan_date_format($results[0]->until) . "</h3><pre>" . $results[0]->text . "</pre></div>";
	$ret .= print_miniplan_upload_form();
	return $ret;
}

//returns a (html)upload form
function print_miniplan_upload_form() {
	$role = array_shift(wp_get_current_user()->roles);
	if ($role !== "administrator") {
		return "";
	} else {
		$content = '<div class="miniplan_admin_panel" id="miniplan_admin_panel"><h3>Miniplan Admin-Panel</h3>';
		/**
		 * VARIABLES--------------------------------------------------------------------
		*/
		$miniplan_upload_form = '<form action="" method="post" class="form-horizontal">
<fieldset>
<legend><h3>Einen neuen Miniplan hochladen</h3></legend>
  <label class="control-label" for="title">Titel</label>
  <div class="controls">
    <input id="title" name="title" placeholder="Miniplan" value="Miniplan" class="input-xlarge" required="" type="text">
    <p class="help-block">Ein einfacher kurzer Name für den Miniplan (z.B. `Miniplan`).</p>
  </div>
  <label class="control-label" for="new_mpl">Miniplan</label>
  <div class="controls">
    <textarea id="new_mpl" name="new_mpl" required>Alle Ministranten</textarea>
  </div>
  <label class="control-label" for="beginning">Beginn</label>
  <div class="controls">
    <select id="beginning" name="beginning" class="input-xlarge" required>
    </select>
  </div>
  <label class="control-label" for="until">Bis</label>
  <div class="controls">
    <select id="beginning" name="beginning" class="input-xlarge" required>
    </select>
    <select id="beginning" name="beginning" class="input-xlarge" required>
    </select>
    <select id="beginning" name="beginning" class="input-xlarge" required>
    </select>
    <input type="datetime" id="until" name="until" class="input-xlarge" required/>
  </div>
</div>

<!-- Button -->
<div class="control-group">
  <label class="control-label" for="submit">Hochladen</label>
  <div class="controls">
    <button id="submit" name="submit" class="btn btn-default">submit</button>
  </div>
</div>
</fieldset>
</form>';
		$miniplan_edit_form = '<p>Editing is coming soon</p>';
		$miniplan_delete_form = '<p>Deleting is coming soon</p>';
		$cancel_btn = '<form method="get" action="#_"><button name="miniplan_admin_action" id="cancel_btn" class="btn btn-default" value="select" type="submit" formmethod="get" formaction="">Abbrechen</button></form>';

		/**
		 * ----------------------------------------------</VARIABLES>
		*/


		switch (get_query_var( "miniplan_admin_action", "" )) {
			case "upload":
				$content .= $miniplan_upload_form . $cancel_btn;
				break;
			case "edit":
				$content .= $miniplan_edit_form. $cancel_btn;
				break;
			case "delete":
				$content .= $miniplan_delete_form. $cancel_btn;
				break;
			default:
				$content .= '<p>Wähle eine Aktion:</p><form action="#miniplan_admin_panel" method="get">
						<button name="miniplan_admin_action" id="upload_btn" class="btn btn-default" value="upload">Einen neuen Plan hochladen</button>
						<button name="miniplan_admin_action" id="edit_btn" class="btn btn-default" value="edit">Einen alten Plan editieren</button>
						<button name="miniplan_admin_action" id="delete_btn" class="btn btn-default" value="delete">Einen alten Plan l&ouml;schen</button>
					</form></div>';
		}
		return $content;
	}
}

//formats a date from a string to a human well readable string
function miniplan_date_format( $strdate ) {
	return date('d.m.y', strtotime($strdate));
}

/**
 *  Register hooks etc.
 */

//GET Parameter ( ?miniplan=x )
function add_miniplan_query_vars_filter( $vars ){
  $vars[] = "miniplan";
  $vars[] = "miniplan_admin_action";
  return $vars;
}
add_filter( 'query_vars', 'add_miniplan_query_vars_filter' );

//DB / [wp_miniplan] )
register_activation_hook( __FILE__, 'miniplan_install' );
register_activation_hook( __FILE__, 'miniplan_install_data' );
add_action( 'plugins_loaded', 'miniplan_update_db_check' );

//SHORTCODES ( [miniplan id="x"] )
add_shortcode( 'miniplan', 'print_miniplan' );
