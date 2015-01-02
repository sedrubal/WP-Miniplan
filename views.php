<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * the user 'interface' and database things
 */

require_once( 'db.php' );

/**
 * prints the miniplan things
 * @param array $atts: the Wordpress tag attributes
 * @return string: the miniplan with (admin)formulars
 */
function print_miniplan( $atts ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'miniplan';

	$date = get_query_var( "miniplan", "latest" );

	$dates = $wpdb->get_results( 'SELECT id,beginning,until FROM `' . $table_name . '` WHERE feedid = \'' . intval($atts["id"]) . '\' ORDER BY beginning DESC', OBJECT ); //get available plans from db (for dropdown)
	//build dropdown
	$ret = '<form action="#_" method="get">
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

		$results = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '` WHERE feedid = \'' . intval($atts["id"]) . '\' AND DATE_FORMAT(beginning, "%Y-%m-%d") = DATE_FORMAT(\'' . date('Y-m-d', $mostRecent) . '\', "%Y-%m-%d") ORDER BY beginning DESC LIMIT 1', OBJECT );
	} else if (is_numeric($date)) {
		$results = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '` WHERE id = \'' . intval($date) . '\' ORDER BY beginning LIMIT 1', OBJECT );
	}
	if ($results === null || $results == "" || empty($results)) {
		if (WP_DEBUG === true) { error_log("Something went wrong, while fetching the miniplan!"); }
		apply_filters('debug', "Something went wrong, while fetching the miniplan!");
		$ret .= '<div class="miniplan" id="miniplan">' . miniplan_message("Der Miniplan ist entweder nicht verfügbar oder kann aufgrund eines internen Fehlers nicht angezeigt werden.", "error") . '</div>';
		$ret .= print_miniplan_admin_form( intval($atts["id"]), null );
	} else {
		//display miniplan
        	$ret .= '<div id="miniplan" class="miniplan"><h3>' . $results[0]->title . ' - gültig vom ' . miniplan_date_format($results[0]->beginning) . ' bis zum ' . miniplan_date_format($results[0]->until) . '</h3>
        	        <!--[if IE 6]>' . miniplan_message( 'Dieser Browser wird nicht unterstützt.' , 'error' ) . '<![endif]-->
        	                            <pre>' . $results[0]->text . '</pre></div>';
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
	if ($role !== "administrator") {
		return "";
	} else {
		$master_mpl = new stdClass();
		if ($current_mpl === null || get_query_var( "miniplan_admin_action", "" ) === "upload") {
			$master_mpl->title = "Miniplan";
			$master_mpl->text = "Alle Ministranten";
			$master_mpl->beginning = miniplan_date_format(current_time( 'd.m.y' ));
			$master_mpl->until = miniplan_date_format(date('d.m.y', strtotime('+1 week')));
		} else {
			$master_mpl->title = ((strlen($current_mpl->title) === 0) ? 'Miniplan' : $current_mpl->title);
			$master_mpl->text = ((strlen($current_mpl->text) === 0 ? "Alle Ministranten" : $current_mpl->text));
			$master_mpl->beginning = ((strlen($current_mpl->beginning) === 0 ? miniplan_date_format(current_time( 'd.m.y' )) : miniplan_date_format($current_mpl->beginning)));
			$master_mpl->until = ((strlen($current_mpl->until) === 0 ? miniplan_date_format(strtotime('+1 week')) : miniplan_date_format($current_mpl->until)));
			$master_mpl->id = $current_mpl->id;
		}
		$content = '<div class="miniplan_admin_panel" id="miniplan_admin_panel"><h3>Miniplan Admin-Panel</h3>';
		/**
		 * VARIABLES--------------------------------------------------------------------
		*/
		//TODO update second datepicker (+1 week) when fist value changed
		$miniplan_edit_form = '<form action="" method="post" class="form-horizontal"><fieldset>

	<legend><h3>' . ((get_query_var( "miniplan_admin_action", "upload" ) === 'upload') ? 'Einen neuen Miniplan hochladen' : 'Den ausgew&auml;hlten Miniplan bearbeiten') . '</h3></legend>
	<label class="control-label" for="title">Titel</label>
	<div class="controls">
		<input id="title" name="mpl_title" placeholder="Miniplan" value="' . $master_mpl->title . '" class="input-xlarge" required type="text">
		<p class="help-block">Ein einfacher kurzer Name für den Miniplan (z.B. "Miniplan").</p>
	</div>

	<label class="control-label" for="new_mpl">Miniplan</label>
	<div class="controls">
		<textarea id="new_mpl" name="mpl_text" required>' . $master_mpl->text . '</textarea>
	</div>

	<label class="control-label" for="beginning-datepicker">Beginn</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="Beginn (d.m.y)"  id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . $master_mpl->beginning . '"> \');

			jQuery(document).ready(function() {
				jQuery("#beginning-datepicker").datepicker({
					dateFormat : "d.m.y"
				});
			});
	        </script>
		<noscript><input type="datetime" placeholder="Beginn (d.m.y)" id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . $master_mpl->beginning . '"/></noscript>
	</div>

	<label class="control-label" for="until-datepicker">Bis</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="bis (d.m.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . $master_mpl->until . '"> \');

			jQuery(document).ready(function() {
				jQuery("#until-datepicker").datepicker({
					dateFormat : "d.m.y"
				});
			});
	        </script>
		<noscript><input type="datetime" placeholder="bis (d.m.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . $master_mpl->until . '"/></noscript>
	</div>

	<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default" value="TRUE">' . ((get_query_var( "miniplan_admin_action", "upload" ) === 'upload') ? 'Hochladen' : 'Aktualisieren') . '</button>
		</div>
	</div>
</fieldset></form>';
		$miniplan_delete_form = '<p>Deleting is coming soon</p>';
		$cancel_btn = '<form method="get" action="#_"><button name="miniplan_admin_action" id="cancel_btn" class="btn btn-default" value="select" type="submit" formmethod="get" formaction="">Abbrechen</button><input type="hidden" name="miniplan" value="' . $master_mpl->id . '" /></form>';
		$proceed_btn = '<form method="get" action="#_"><button name="miniplan_admin_action" id="proceed_btn" class="btn btn-default" value="proceed" type="submit" formmethod="get" formaction="">Fortfahren</button> <input type="hidden" name="miniplan" value="' . $master_mpl->id . '" /></form>';

		/**
		 * ----------------------------------------------</VARIABLES>
		*/
		$submitted = (get_query_var( "mpl_submit", "false" ) === "TRUE");

		switch (get_query_var( "miniplan_admin_action", "" )) {
			case "upload":
				if ($submitted) {
					miniplan_add_new($feed_id, get_query_var( "mpl_title", "" ), get_query_var( "mpl_text", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich hochgeladen.", "success") . $proceed_btn;
				} else {
					$content .= $miniplan_edit_form . $cancel_btn;
				}
				break;
			case "edit":
				if ($submitted) {
					miniplan_edit_existing($master_mpl->id, $feed_id, get_query_var( "mpl_title", "" ), get_query_var( "mpl_text", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich bearbeitet.", "success") . $proceed_btn;
				} else {
					$content .= $miniplan_edit_form . $cancel_btn;
				}
				break;
			case "delete":
				$content .= $submitted ? (miniplan_message("Der Miniplan wurde erfolgreich gelöscht.", "success") . $proceed_btn) : ($miniplan_delete_form. $cancel_btn);
				break;
			default:
				$content .= '<p>Wähle eine Aktion:</p><form action="#miniplan_admin_panel" method="get">
						<input type="hidden" name="miniplan" value="' . $master_mpl->id . '" />
						<button name="miniplan_admin_action" id="upload_btn" class="btn btn-default" value="upload">Einen neuen Plan hochladen</button>
						<button name="miniplan_admin_action" id="edit_btn" class="btn btn-default" value="edit" ' . ((count($current_mpl) === 0) ? 'disabled' : '') . '>Den ausgew&auml;hlten Plan editieren</button>
						<button name="miniplan_admin_action" id="delete_btn" class="btn btn-default" value="delete" ' . ((count($current_mpl) === 0) ? 'disabled' : '') . '>Den ausgew&auml;hlten Plan l&ouml;schen</button>
					</form></div>';
		}
		return $content;
	}
}


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
 * Genereates a html div containing the message
 * @param string $message: The message to be displayed
 * @param string $state: 'success' for success, anything else for error
 * @return string: A html div, containing the message
 */
function miniplan_message( $message , $state ) {
	return '<div class="messagebox ' . ($state === "success" ? 'success" style="border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#55AA55;"' : 'error" style="border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#AA5555;"') . ' id="messagebox"><h4>' . $message . '</h4></div>';
}
