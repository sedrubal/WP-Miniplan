<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * the user 'interface' and database things
 */

require_once( 'db.php' );

/**
 * prints the miniplan things
 * @param array $atts: the Wordpress tag attributes
 * @return string: the miniplan with (admin)forms
 */
function print_miniplan( $atts ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'miniplan';

	$date = get_query_var( "miniplan", "latest" );
	if ( !is_numeric($date)) { $date = "latest"; }

	$dates = $wpdb->get_results( 'SELECT id,beginning,until FROM `' . $table_name . '` WHERE feed_id = \'' . intval($atts["id"]) . '\' ORDER BY beginning DESC', OBJECT ); //get available plans from db (for dropdown)
	//create dropdown
	$ret = '<div id="miniplan" class="miniplan"><form action="' . $_SERVER['REQUEST_URI'] . '#miniplan" method="get">
		Datum: <select onchange="this.form.submit()" name="miniplan" required>
			<option value="latest" ' . ($date === 'latest' ? ' selected' : '') . ' >aktueller Plan</option>';
	//options from db
	foreach ($dates as $d) {
		$ret .= "<option value='" . $d->id . "'" . (($date === $d->id) ? " selected>" : ">") . miniplan_date_format($d->beginning) . " - "  . miniplan_date_format($d->until) . "</option>";
	}
	$ret .="</select>
		<noscript><button type='submit'>aktualisieren</button></noscript>
	</form>";
	//query selected miniplan from db
    $results = null;
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

		$results = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '` WHERE feed_id = \'' . intval($atts["id"]) . '\' AND DATE_FORMAT(beginning, "%Y-%m-%d") = DATE_FORMAT(\'' . date('Y-m-d', $mostRecent) . '\', "%Y-%m-%d") ORDER BY beginning DESC LIMIT 1', OBJECT );
	} else if (is_numeric($date)) {
		$results = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '` WHERE id = \'' . intval($date) . '\' ORDER BY beginning LIMIT 1', OBJECT );
	}
	if ($results === null || $results == "" || empty($results)) {
		if (WP_DEBUG === true) { error_log("Something went wrong, while fetching the miniplan!"); }
		apply_filters('debug', "Something went wrong, while fetching the miniplan!");
		$ret .= miniplan_message("Der Miniplan ist entweder nicht verfügbar oder kann aufgrund eines internen Fehlers nicht angezeigt werden.", "error") . '</div>';
		$ret .= print_miniplan_admin_form( intval($atts["id"]), null );
	} else {
		//display miniplan
        	$ret .= '<h3>' . $results[0]->title . ' - gültig vom ' . miniplan_date_format($results[0]->beginning) . ' bis zum ' . miniplan_date_format($results[0]->until) . '</h3>
        	        <!--[if IE 6]>' . miniplan_message( 'Dieser Browser wird nicht unterstützt.' , 'error' ) . '<![endif]-->' .
						((strlen($results[0]->attendance) > 0) ? '<div id="attendance"><strong><p>Bereitschaft: ' . $results[0]->attendance . '</strong></p></div>' : '') .
						'<pre id="miniplan_content">' . $results[0]->text . '</pre>' .
						((strlen($results[0]->notification) > 0) ? ('</br>' . miniplan_message($results[0]->notification, 'notification')) : '') . '</div>';
		$ret .= print_miniplan_admin_form( intval($atts["id"]) , $results[0]);
	}
	return $ret;
}

/**
 * @param $feed_id: the current feed id (int)
 * @param $current_mpl: A standard class containing the values of the current miniplan
 * @return string: A html upload and edit form
 */
function print_miniplan_admin_form( $feed_id , $current_mpl ) {
	$role = array_shift(wp_get_current_user()->roles);
	if ( !in_array(strtolower($role), get_option('miniplan_privileged_roles')) ) {
		return "";
	} else {
		$master_mpl = new stdClass();
		if ($current_mpl === null || get_query_var( "miniplan_admin_action", "" ) === "upload") {
			$master_mpl->title = "Miniplan";
			$master_mpl->text = '';
			$master_mpl->attendance = '';
			$master_mpl->notification = '';
			$master_mpl->beginning = miniplan_date_format(current_time( 'd.m.y' ));
			$master_mpl->until = miniplan_date_format(date('d.m.y', strtotime('+1 week')));
			$master_mpl->id = -1;
		} else {
			$master_mpl->title = ((strlen($current_mpl->title) === 0) ? 'Miniplan' : $current_mpl->title);
			$master_mpl->text = ((strlen($current_mpl->text) === 0 ? '' : $current_mpl->text));
			$master_mpl->attendance = ((strlen($current_mpl->attendance) === 0 ? '' : $current_mpl->attendance));
			$master_mpl->notification = ((strlen($current_mpl->notification) === 0 ? '' : $current_mpl->notification));
			$master_mpl->beginning = ((strlen($current_mpl->beginning) === 0 ? miniplan_date_format(current_time( 'd.m.y' )) : miniplan_date_format($current_mpl->beginning)));
			$master_mpl->until = ((strlen($current_mpl->until) === 0 ? miniplan_date_format(strtotime('+1 week')) : miniplan_date_format($current_mpl->until)));
			$master_mpl->id = $current_mpl->id;
		}
		$content = '<div class="miniplan_admin_panel" id="miniplan_admin_panel"><h3>Miniplan Admin-Panel</h3>';
		/**
		 * VARIABLES--------------------------------------------------------------------
		*/
		//TODO update second datepicker (+1 week) when fist value changed
		$miniplan_edit_form = '<form action="' . $_SERVER['REQUEST_URI'] . '#miniplan_admin_panel" method="post" class="form-horizontal"><fieldset>
	<legend><h3>' . ((get_query_var( "miniplan_admin_action", "upload" ) === 'upload') ? 'Einen neuen Miniplan hochladen' : 'Den ausgew&auml;hlten Miniplan bearbeiten') . '</h3></legend>
	<label class="control-label" for="title">Titel:</label>
	<div class="controls">
		<input id="title" name="mpl_title" placeholder="Miniplan" value="' . $master_mpl->title . '" class="input-xlarge" required type="text" style="width:90%;">
		<p class="help-block">Ein einfacher kurzer Name für den Miniplan (z.B. "Miniplan").</p>
	</div>

	<label class="control-label" for="attendance">Bereitschaft:</label>
	<div class="controls">
		<input id="attendance" name="mpl_attendance" placeholder="Bereitschaft" value="' . $master_mpl->attendance . '" class="input-xlarge" type="text" style="width:90%;">
		<p class="help-block">Namen der Ministranten, die zur Bereitschaft aufgestellt sind.</p>
	</div>

	<label class="control-label" for="new_mpl">Miniplan:</label>
	<div class="controls">
		<script>
			function textAreaAdjust(o) {
				o.style.height = "1px";
				o.style.height = (o.scrollHeight)+"px";
			}
		</script>
		<textarea id="new_mpl" name="mpl_text" required placeholder="Miniplan" onkeyup="textAreaAdjust(this)" style="height:25em; width:90%;">' . $master_mpl->text . '</textarea>
	</div>

	<label class="control-label" for="notification">Benachrichtigungen:</label>
	<div class="controls">
		<input id="notification" name="mpl_notification" placeholder="Benachrichtigungen" value="' . $master_mpl->notification . '" class="input-xlarge" type="text" style="width:90%;">
		<p class="help-block">Falls Miniproben o.&Auml;. anstehen, kann man das hier eintragen.</p>
	</div>

	<label class="control-label" for="beginning-datepicker">Beginn:</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="Beginn (dd.mm.y)"  id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . $master_mpl->beginning . '" style="width:90%;"> \');

			jQuery(document).ready(function() {
				jQuery("#beginning-datepicker").datepicker({
					dateFormat : "dd.mm.y"
				});
			});
	        </script>
		<noscript><input type="datetime" placeholder="Beginn (dd.mm.y)" id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . $master_mpl->beginning . '"/ style="width:90%;"></noscript>
	</div>

	<label class="control-label" for="until-datepicker">Bis:</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="bis (dd.mm.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . $master_mpl->until . '" style="width:90%;"> \');

			jQuery(document).ready(function() {
				jQuery("#until-datepicker").datepicker({
					dateFormat : "dd.mm.y"
				});
			});
	        </script>
		<noscript><input type="datetime" placeholder="bis (dd.mm.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . $master_mpl->until . '"/ style="width:90%;"></noscript>
	</div>

	<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default" value="TRUE">' . ((get_query_var( "miniplan_admin_action", "upload" ) === 'upload') ? 'Hochladen' : 'Aktualisieren') . '</button>
		</div>
	</div>
</fieldset></form>';

		$miniplan_delete_form = '<form action="' . $_SERVER['REQUEST_URI'] . '#miniplan_admin_panel" method="post" class="form-horizontal"><fieldset>
	<legend><h3>Ausgew&auml;hlten Miniplan l&ouml;schen</h3></legend>' .
	miniplan_message('M&ouml;chtest du wirklich den Plan "' . $master_mpl->title . '" vom ' . $master_mpl->beginning . ' bis zum ' . $master_mpl->until . ' l&ouml;schen?', "question") .
	'<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default delete" value="TRUE">L&ouml;schen</button>
		</div>
	</div>
</fieldset></form>';
		$cancel_btn = '<form method="get" action="' . $_SERVER['REQUEST_URI'] . '#miniplan">
				<button name="miniplan_admin_action" id="cancel_btn" class="btn btn-default cancel" value="select" type="submit" formmethod="get" >Abbrechen</button>
				<input type="hidden" name="miniplan" value="' . $master_mpl->id . '" /></form>';
		$proceed_btn = '<form method="get" action="' . $_SERVER['REQUEST_URI'] . '#miniplan"><button name="miniplan_admin_action" id="proceed_btn" class="btn btn-default" value="proceed" type="submit" formmethod="get" >Fortfahren</button>
				<input type="hidden" name="miniplan" value="' . ((get_query_var( "miniplan_admin_action", "" ) === 'delete') ? 'latest' : $master_mpl->id) . '" /></form>';

		/**
		 * ----------------------------------------------</VARIABLES>
		*/
		$submitted = (get_query_var( "mpl_submit", "false" ) === "TRUE");

		switch (get_query_var( "miniplan_admin_action", "" )) {
			case "upload":
				if ($submitted) {
					miniplan_add_new($feed_id, get_query_var( "mpl_title", "" ), get_query_var( "mpl_text", "" ), get_query_var( "mpl_attendance", "" ), get_query_var( "mpl_notification", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich hochgeladen.", "success") . $proceed_btn . '</div>';
				} else {
					$content .= $miniplan_edit_form . $cancel_btn . '</div>';
				}
				break;
			case "edit":
				if ($submitted) {
					miniplan_edit_existing($master_mpl->id, $feed_id, get_query_var( "mpl_title", "" ), get_query_var( "mpl_text", "" ), get_query_var( "mpl_attendance", "" ), get_query_var( "mpl_notification", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich bearbeitet.", "success") . $proceed_btn . '</div>';
				} else {
					$content .= $miniplan_edit_form . $cancel_btn . '</div>';
				}
				break;
			case "delete":
				if ($submitted) {
					miniplan_delete_existing($master_mpl->id);
					$content .= miniplan_message("Der Miniplan wurde erfolgreich gel&ouml;scht.", "success") . $proceed_btn . '</div>';
				} else {
					$content .= $miniplan_delete_form . $cancel_btn . '</div>';
				}
				break;
			default:
				$content .= '<p>Wähle eine Aktion:</p><form action="' . $_SERVER['REQUEST_URI'] . '#miniplan_admin_panel" method="get">
						<input type="hidden" name="miniplan" value="' . $master_mpl->id . '" />
						<button name="miniplan_admin_action" id="upload_btn" class="btn btn-default upload" value="upload">Einen neuen Plan hochladen</button>
						<button name="miniplan_admin_action" id="edit_btn" class="btn btn-default edit" value="edit" ' . ((count($current_mpl) === 0) ? 'disabled' : '') . '>Den ausgew&auml;hlten Plan editieren</button>
						<button name="miniplan_admin_action" id="delete_btn" class="btn btn-default delete" value="delete" ' . ((count($current_mpl) === 0) ? 'disabled' : '') . '>Den ausgew&auml;hlten Plan l&ouml;schen</button>
					</form></div>';
		}
		return $content;
	}
}


/**
 * creates the admin menu page in wordpress admin section
 */
function miniplan_menu_pages() {
	// Add the top-level admin menu
	$page_title = 'Miniplan Settings';
	$menu_title = 'Miniplan';
	$capability = 'manage_options';
	$menu_slug = 'miniplan-admin-settings';
	$function = 'miniplan_admin_settings';
	//add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);
	add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);

	// Add submenu page with same slug as parent to ensure no duplicates
	//$sub_menu_title = 'Einstellungen';
	//add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $function);

	/*// Now add the submenu page for Help
	$submenu_page_title = 'Miniplan Hilfe';
	$submenu_title = 'Hilfe';
	$submenu_slug = 'miniplan-admin-help';
	$submenu_function = 'miniplan_admin_help';
	add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);*/
}

/**
 * creates the settings tab
 */
function miniplan_admin_settings() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}

	global $wp_roles;
	$privileged_roles = array();
	$changes = false;
	if (isset($_POST['reset'])) {
		$privileged_roles = $miniplan_default_privileged_roles;
		$changes = true;
	} else if (isset($_POST['submit'])) {
		foreach ($wp_roles->get_names() as $role) {
			if (isset($_POST['role_' . strtolower($role)])) {
				if ($_POST['role_' . strtolower($role)] === "true") {
					$privileged_roles[] = strtolower($role);
				}
			}
		}
		$changes = true;
	}
	if ($changes) {
		if (sizeof($privileged_roles) > 0) {
			update_option('miniplan_privileged_roles', $privileged_roles);
			echo '<div id="message" class="updated fade"><p><strong>' . __('Options saved.') . '</strong></p></div>';
		} else { echo '<div id="message" class="error fade"><p><strong>Es wurde keine Rolle als priviligiert ausgew&auml;lt. Die Einstellungen wurden nicht gespeichert!</strong></p></div>'; }
	}


	?>
                <div class="wrap">
                        <h2>Miniplan <?php _e('Settings')?></h2>
			<!---<p>Sie benutzen Version ' . '' . '</p>--->
			<p class="howto">Beachte: Wenn du einen Miniplan auf einer Seite anzeigen m&ouml;chtest, dann f&uuml;ge einfach den text <code>[miniplan id="x"]</code> in den Post oder auf in die Seite ein. (x steht f&uuml;r die id des Feeds. Falls du nur einen Miniplan auf deiner Wordpress Seite anzeigen willst, dann verwende einfach 1).</p>
                        <form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                                <table class="form-table">
                                        <tbody>
                                                <tr valign="top">
                                                        <th scope="row">
                                                                <label for="miniplan_privileged_roles">Privilegierte User Rollen:</label>
								<p class="howto">Ein User muss in einer der ausgew&auml;hlten Rolle sein, um einen Miniplan zu bearbeiten.</p>
                                                        </th>
                                                        <td>
								<fieldset id="miniplan_privileged_roles">
								<?php
									foreach ($wp_roles->get_names() as $role) {
										echo '<label for="role_' . strtolower($role) . '">
											<input name="role_' . strtolower($role) . '" id="role_' . strtolower($role) . '" value="true" ' . (in_array(strtolower($role), get_option('miniplan_privileged_roles')) ? 'checked="checked"' : '') . ' type="checkbox">
										' . __($role) . '</label><br>';
									}
								?>
								</fieldset>
                                                        </td>
                                        </tbody>
                                </table>
				<p class="submit">
					<input type="submit" name="submit" value="<?php echo _e('Save Changes'); ?>" class="button button-primary">
					<input type="submit" name="reset" value="<?php echo _e('Reset Changes'); ?>" class="button button-secondary reset">
				</p>
                        </form>
                </div>
	<?php

}

/*function miniplan_admin_help() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}

	echo '<p>hello world</p>';
}*/

/**
 * formats a date from a string to a human well readable string or a human well readable date to a sql readable date
 * @param string $strdate: the date as string
 * @param string $mode: 'human' or 'sql'
 * @return bool|string: the new string containing the date or false, if an error occurred
 */
function miniplan_date_format( $strdate , $mode="human") {
	if ($mode === "human") {
		return date('d.m.y', strtotime($strdate));
	} else {
		$pts = explode(".", $strdate, 3);
		return date('Y-m-d', strtotime($pts[2] . "-" . $pts[1] . "-" . $pts[0]));
	}
}

/**
 * Generates a html div containing the message
 * @param string $message: The message to be displayed
 * @param string $state: 'success' for success, anything else for error
 * @return string: A html div, containing the message
 */
function miniplan_message( $message , $state ) {
	return '<div class="messagebox ' . ($state === "success" ? 'success" style="border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#55AA55;"' : 'error" style="border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#AA5555;"') . ' id="messagebox"><h4>' . $message . '</h4></div>';
}
