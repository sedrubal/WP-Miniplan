<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * the user 'interface' and database things
 */

function print_miniplan( $atts ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'miniplan';

	$date = get_query_var( "miniplan", "latest" );

	$dates = $wpdb->get_results( 'SELECT id,beginning,until FROM `' . $table_name . '` WHERE feedid = \'' . intval($atts["id"]) . '\' ORDER BY beginning DESC', OBJECT ); //get available plans from db (for dropdown)
	//build dropdown
	$ret = "<form action='#_' method='get'>
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
		$ret .= '<div class="miniplan" id="miniplan">' . miniplan_message("Der Miniplan ist entweder nicht verfügbar oder kann aufgrund eines internen Fehlers nicht angezeigt werden.", "error") . '</div>';
		$ret .= print_miniplan_upload_form( $atts );
	} else {
		//display miniplan
        	$ret .= "<div id='miniplan' class='miniplan'><h3>" . $results[0]->title . " - gültig vom " . miniplan_date_format($results[0]->beginning) . " bis zum " . miniplan_date_format($results[0]->until) . "</h3><pre>" . $results[0]->text . "</pre></div>";
		$ret .= print_miniplan_upload_form( $atts , $results);
	}
	return $ret;
}

//returns a (html)upload form
function print_miniplan_upload_form( $atts , $current_mpl=array() ) {
	$role = array_shift(wp_get_current_user()->roles);
	if ($role !== "administrator") {
		return "";
	} else {
		$content = '<div class="miniplan_admin_panel" id="miniplan_admin_panel"><h3>Miniplan Admin-Panel</h3>';
		/**
		 * VARIABLES--------------------------------------------------------------------
		*/
		//TODO update second datepicker (+1 week) when fist value changed
		$miniplan_upload_form = '<form action="" method="post" class="form-horizontal"><fieldset>

	<legend><h3>Einen neuen Miniplan hochladen</h3></legend>
	<label class="control-label" for="title">Titel</label>
	<div class="controls">
		<input id="title" name="mpl_title" placeholder="Miniplan" value="' . get_query_var( 'mpl_title', 'Miniplan' ) . '" class="input-xlarge" required="" type="text">
		<p class="help-block">Ein einfacher kurzer Name für den Miniplan (z.B. "Miniplan").</p>
	</div>

	<label class="control-label" for="new_mpl">Miniplan</label>
	<div class="controls">
		<textarea id="new_mpl" name="mpl_text" required>' . get_query_var( 'mpl_text', 'Alle Ministranten' ) . '</textarea>
	</div>

	<label class="control-label" for="beginning-datepicker">Beginn</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="Beginn (d.m.y)"  id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . get_query_var( 'mpl_beginning', miniplan_date_format(current_time( 'd.m.y' ))) . '"> \');

			jQuery(document).ready(function() {
				jQuery("#beginning-datepicker").datepicker({
					dateFormat : "d.m.y"
				});
			});
	        </script>
		<noscript><input type="datetime" placeholder="Beginn (d.m.y)" id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . get_query_var( 'mpl_beginning', miniplan_date_format(current_time( 'd.m.y' ))) . '"/></noscript>
	</div>

	<label class="control-label" for="until-datepicker">Bis</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="bis (d.m.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . get_query_var( 'mpl_until', miniplan_date_format(date('d.m.y', strtotime('+1 week')))) . '"> \');

			jQuery(document).ready(function() {
				jQuery("#until-datepicker").datepicker({
					dateFormat : "d.m.y"
				});
			});
	        </script>
		<noscript><input type="datetime" placeholder="bis (d.m.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . get_query_var( 'mpl_until', miniplan_date_format(strtotime('+1 week'))) . '"/></noscript>
	</div>

	<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default" value="TRUE">Hochladen</button>
		</div>
	</div>
</fieldset></form>';
		$miniplan_edit_form = '<p>Editing is coming soon</p>';
		$miniplan_delete_form = '<p>Deleting is coming soon</p>';
		$cancel_btn = '<form method="get" action="#_"><button name="miniplan_admin_action" id="cancel_btn" class="btn btn-default" value="select" type="submit" formmethod="get" formaction="">Abbrechen</button></form>';
		$proceed_btn = '<form method="get" action="#_"><button name="miniplan_admin_action" id="proceed_btn" class="btn btn-default" value="proceed" type="submit" formmethod="get" formaction="">Fortfahren</button></form>';

		/**
		 * ----------------------------------------------</VARIABLES>
		*/
		$submitted = (get_query_var( "mpl_submit", "false" ) === "TRUE");

		switch (get_query_var( "miniplan_admin_action", "" )) {
			case "upload":
				if ($submitted) {
					miniplan_add_new(intval($atts["id"]), get_query_var( "mpl_title", "" ), get_query_var( "mpl_text", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich hochgeladen.", "success") . $proceed_btn;
				} else {
					$content .= $miniplan_upload_form . $cancel_btn;
				}
				break;
			case "edit":
				$content .= $submitted ? (miniplan_message("Der Miniplan wurde erfolgreich bearbeitet.", "success") . $proceed_btn) : ($miniplan_edit_form. $cancel_btn);
				break;
			case "delete":
				$content .= $submitted ? (miniplan_message("Der Miniplan wurde erfolgreich gelöscht.", "success") . $proceed_btn) : ($miniplan_delete_form. $cancel_btn);
				break;
			default:
				$content .= '<p>Wähle eine Aktion:</p><form action="#miniplan_admin_panel" method="get">
						<button name="miniplan_admin_action" id="upload_btn" class="btn btn-default" value="upload">Einen neuen Plan hochladen</button>
						<button name="miniplan_admin_action" id="edit_btn" class="btn btn-default" value="edit" ' . ((count($current_mpl) === 0) ? 'disabled' : '') . '>Den ausgew&auml;hlten Plan editieren</button>
						<button name="miniplan_admin_action" id="delete_btn" class="btn btn-default" value="delete" ' . ((count($current_mpl) === 0) ? 'disabled' : '') . '>Den ausgew&auml;hlten Plan l&ouml;schen</button>
					</form></div>';
		}
		return $content;
	}
}

//formats a date from a string to a human well readable string
function miniplan_date_format( $strdate , $mode="human") {
	if ($mode === "human") {
		return date('d.m.y', strtotime($strdate));
	} else {
		$pts = explode(".", $strdate, 3);
		return date('Y-m-d', strtotime($pts[2] . "-" . $pts[1] . "-" . $pts[0]));
	}
}

function miniplan_message( $message , $state ) {
	return '<div class="messagebox ' . ($state === "success" ? 'success" style="border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#55AA55;"' : 'error" style="border:1px solid black;margin:5px;padding:5px;text-align:center;color:white;background-color:#AA5555;"') . ' id="messagebox"><h4>' . $message . '</h4></div>';
}

function miniplan_add_new( $feedid, $title, $text, $beginning, $until) {
	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->insert(
			$table_name,
			array(
				'feedid' 	=> $feedid,
				'beginning' 	=> miniplan_date_format($beginning, "sql"),
				'until' 	=> miniplan_date_format($until, "sql"),
				'title' 	=> $title,
				'text' 		=> $text
			),
			array( '%d' , '%s', '%s', '%s', '%s')
	);
}
