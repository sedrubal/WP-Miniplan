<?php
/**
 * the user 'interface' and database things
 */

defined('MINIPLAN_ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

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
		$current_mpl->beginning = (isset($current_mpl->beginning) && is_string($current_mpl->beginning)) ? new DateTime($current_mpl->beginning) : NULL;
		$current_mpl->until = (isset($current_mpl->until) && is_string($current_mpl->until)) ? new DateTime($current_mpl->until) : NULL;
		$master_mpl = new stdClass();
		$new_def = ($current_mpl === null || get_query_var( "miniplan_admin_action", "" ) === "upload");
		$master_mpl->text         = (($new_def || (strlen($current_mpl->text) === 0)         ? ('')         : ($current_mpl->text)));
		$master_mpl->attendance   = (($new_def || (strlen($current_mpl->attendance) === 0)   ? ('')         : ($current_mpl->attendance)));
		$master_mpl->notification = (($new_def || (strlen($current_mpl->notification) === 0) ? ('')         : ($current_mpl->notification)));
		$master_mpl->beginning    = (($new_def || ! $current_mpl->beginning instanceof DateTime) ? (DateTime::createFromFormat('d.m.y', date('d.m.y'))) : ($current_mpl->beginning));
		$master_mpl->until = clone $master_mpl->beginning;
		$master_mpl->until        = (($new_def || ! $current_mpl->until instanceof DateTime) ? ($master_mpl->until->add(date_interval_create_from_date_string('6 days'))) : ($current_mpl->until));
		$master_mpl->id           = (($new_def || !is_numeric($current_mpl->id))             ? (-1)         : (intval($current_mpl->id)));

		$content = '<div class="miniplan_admin_panel" id="miniplan_admin_panel"><h3>Miniplan Admin-Panel</h3>';

		/**
		 * VARIABLES--------------------------------------------------------------------
		*/
		$miniplan_edit_form = '<form action="' . $_SERVER['REQUEST_URI'] . '#miniplan_admin_panel" method="post" class="form-horizontal"><fieldset>
	<legend><h3>' . ((get_query_var( "miniplan_admin_action", "upload" ) === 'upload') ? 'Einen neuen Miniplan hochladen' : 'Den ausgew&auml;hlten Miniplan bearbeiten') . '</h3></legend>

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
		<textarea id="new_mpl" name="mpl_text" required placeholder="Miniplan" onkeyup="textAreaAdjust(this)" style="font-family:\'Lucida Console\', monospace; height:25em; width:90%;">' . $master_mpl->text . '</textarea>
	</div>

	<label class="control-label" for="notification">Benachrichtigungen:</label>
	<div class="controls">
		<input id="notification" name="mpl_notification" placeholder="Benachrichtigungen" value="' . $master_mpl->notification . '" class="input-xlarge" type="text" style="width:90%;">
		<p class="help-block">Falls Miniproben o.&Auml;. anstehen, kann man das hier eintragen.</p>
	</div>

	<label class="control-label" for="beginning-datepicker">Beginn:</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="Beginn (dd.mm.y)"  id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . miniplan_date_format($master_mpl->beginning) . '" style="width:90%;"> \');

			jQuery(document).ready(function() {
				jQuery("#beginning-datepicker").datepicker({
					dateFormat : "dd.mm.y",
					onSelect: function(dateText, inst) {
						var newDateBeg = jQuery.datepicker.parseDate("dd.mm.y", dateText);
						var newDateUntil = new Date(newDateBeg.getFullYear(), newDateBeg.getMonth(), newDateBeg.getDate() + 6);
						jQuery("#until-datepicker").val(jQuery.datepicker.formatDate("dd.mm.y", newDateUntil));
										}
				});
			});
			</script>
		<noscript><input type="datetime" placeholder="Beginn (dd.mm.y)" id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . miniplan_date_format($master_mpl->beginning) . '"/ style="width:90%;"></noscript>
	</div>

	<label class="control-label" for="until-datepicker">Bis:</label>
	<div class="controls">
		<script type="text/javascript">
			document.write(\' <input type="text" placeholder="bis (dd.mm.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . miniplan_date_format($master_mpl->until) . '" style="width:90%;"> \');

			jQuery(document).ready(function() {
				jQuery("#until-datepicker").datepicker({
					dateFormat : "dd.mm.y",
				});
			});
			</script>
		<noscript><input type="datetime" placeholder="bis (dd.mm.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . miniplan_date_format($master_mpl->until) . '"/ style="width:90%;"></noscript>
	</div>

<!---
	<label class="control-label" for="datepicker">Datum:</label>
	<div class="controls">
		<script>
			document.write( \'<style>.ui-datepicker,.ui-widget,.ui-widget-content,.ui-helper-clearfix{width: 100%;padding-left: 1em;padding-right: 1em;margin-top: 1em;margin-bottom: 1em;}div.ui-datepicker-inline {width: 90%;}a.ui-state-default {text-align: center;}.ui-datepicker-calendar .dp-highlight{background: #484;color: #FFF;}</style>\');
			document.write( \'<div id="datepicker"></div><p>Gilt vom <input placeholder="Beginn (dd.mm.y)" id="beginning-datepicker" name="mpl_beginning" class="input-xlarge hasDatepicker" required="required" style="size=10;" type="text"> \
				bis <input placeholder="Beginn (dd.mm.y)" id="until-datepicker" name="mpl_beginning" class="input-xlarge hasDatepicker" required="required" style="size=10;" type="text">\' );

					/*
					 * jQuery UI Datepicker: Using Datepicker to Select Date Range
					 * http://salman-w.blogspot.com/2013/01/jquery-ui-datepicker-examples.html
					 */

					jQuery(function() {
							jQuery("#datepicker").datepicker({
									beforeShowDay: function(date) {
											var date1 = jQuery.datepicker.parseDate(jQuery.datepicker._defaults.dateFormat, jQuery("#beginning-datepicker").val());
											var date2 = jQuery.datepicker.parseDate(jQuery.datepicker._defaults.dateFormat, jQuery("#until-datepicker").val());
											return [true, date1 && ((date.getTime() == date1.getTime()) || (date2 && date >= date1 && date <= date2)) ? "dp-highlight" : ""];
									},
									onSelect: function(dateText, inst) {
											var date1 = jQuery.datepicker.parseDate(jQuery.datepicker._defaults.dateFormat, jQuery("#beginning-datepicker").val());
											var date2 = jQuery.datepicker.parseDate(jQuery.datepicker._defaults.dateFormat, jQuery("#until-datepicker").val());
											if (!date1 || date2) {
													jQuery("#beginning-datepicker").val(dateText);
													jQuery("#until-datepicker").val("");
													jQuery(this).datepicker("option", "minDate", dateText);
											} else {
													jQuery("#until-datepicker").val(dateText);
													jQuery(this).datepicker("option", "minDate", null);
											}
									}
							});
					});
			</script>
		<noscript>
			<label class="control-label" for="beginning-datepicker">Gültig von:</label>
			<div class="controls">
				<input type="datetime" placeholder="Beginn (dd.mm.y)" id="beginning-datepicker" name="mpl_beginning" class="input-xlarge" required value="' . miniplan_date_format($master_mpl->beginning) . '" style="width:90%;">
			</div>
			<label class="control-label" for="until-datepicker">Bis:</label>
			<div class="controls">
				<input type="datetime" placeholder="bis (dd.mm.y)"  id="until-datepicker" name="mpl_until" class="input-xlarge" required value="' . miniplan_date_format($master_mpl->until) . '" style="width:90%;">
			</div>
		</noscript>
	</div>
--->

	<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default" value="TRUE">' . ((get_query_var( "miniplan_admin_action", "upload" ) === 'upload') ? 'Hochladen' : 'Aktualisieren') . '</button>
		</div>
	</div>
</fieldset></form>';

		$miniplan_delete_form = '<form action="' . $_SERVER['REQUEST_URI'] . '#miniplan_admin_panel" method="post" class="form-horizontal"><fieldset>
	<legend><h3>Ausgew&auml;hlten Miniplan l&ouml;schen</h3></legend>' .
	miniplan_message('M&ouml;chtest du den Miniplan vom ' . miniplan_date_format($master_mpl->beginning) . ' bis zum ' . miniplan_date_format($master_mpl->until) . ' l&ouml;schen?', "question") .
	'<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default delete" value="TRUE">L&ouml;schen</button>
		</div>
	</div>
</fieldset></form>';
		$miniplan_delete_current_form = '<form action="' . $_SERVER['REQUEST_URI'] . '#miniplan_admin_panel" method="post" class="form-horizontal"><fieldset>
	<legend><h3>Ausgew&auml;hlten Miniplan l&ouml;schen</h3></legend>' .
	miniplan_message('M&ouml;chtest du wirklich den <b> aktuellen </b> Miniplan vom ' . miniplan_date_format($master_mpl->beginning) . ' bis zum ' . miniplan_date_format($master_mpl->until) . ' l&ouml;schen?', "question") .
	'<div class="control-group">
		<div class="controls">
			<button id="submit" name="mpl_submit" class="btn btn-default delete" value="FORCE">L&ouml;schen</button>
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
		$submitState = (get_query_var( "mpl_submit", "false" ) === "FORCE" ? "FORCE" : get_query_var( "mpl_submit", "false" ) === "TRUE");
//		$submitState = (get_query_var( "mpl_submit", "false" ) === "TRUE" || get_query_var( "mpl_submit", "false" ) === "force");

		switch ( get_query_var( "miniplan_admin_action", "" ) ) {
			case "upload":
				if ($submitState) {
					miniplan_db::add_new_miniplan($feed_id, get_query_var( "mpl_text", "" ), get_query_var( "mpl_attendance", "" ),
                        get_query_var( "mpl_notification", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich hochgeladen.", "success") . $proceed_btn . '</div>';
				} else {
					$content .= $miniplan_edit_form . $cancel_btn . '</div>';
				}
				break;
			case "edit":
				if ($submitState) {
					miniplan_db::edit_existing_miniplan($master_mpl->id, $feed_id, get_query_var( "mpl_text", "" ), get_query_var( "mpl_attendance", "" ),
                        get_query_var( "mpl_notification", "" ), get_query_var( "mpl_beginning", "" ), get_query_var( "mpl_until", "" ));
					$content .= miniplan_message("Der Miniplan wurde erfolgreich bearbeitet.", "success") . $proceed_btn . '</div>';
				} else {
					$content .= $miniplan_edit_form . $cancel_btn . '</div>';
				}
				break;
			case "delete":
				if ($submitState) {
					if ( !$master_mpl->id == "-1" || $submitState == "FORCE" ) {
						miniplan_db::delete_existing_miniplan($master_mpl->id);
						$content .= miniplan_message("Der Miniplan wurde erfolgreich gel&ouml;scht.", "success") . $proceed_btn . '</div>';
					} else {
						$content .= $miniplan_delete_current_form . $cancel_btn . '</div>';
					}
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
}

/*function miniplan_admin_help() {
	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}

	echo '<p>hello world</p>';
}*/

/**
 * formats a date from a string to a human well readable string or a human well readable date to a sql readable date
 * @param DateTime|string $strdate: the date as string
 * @param string $mode: 'human' or 'sql'
 * @return bool|string: the new string containing the date or false, if an error occurred
 */
function miniplan_date_format( $strdate , $mode="human") {
	$dt_date = new DateTime();
	if (is_string($strdate)) {
		if (strpos($strdate, '.')) {
			$pts = explode('.', $strdate, 3);
			$dt_date = new DateTime($pts[2] . '-' . $pts[1] . '-' . $pts[0]); //Y-m-d
		} else if (strpos($strdate, '-')) {
			$dt_date = new DateTime($strdate);
		}
	} else {
		$dt_date = $strdate;
	}

	if ($dt_date instanceof DateTime) {
		return ($mode === "human") ? $dt_date->format('d.m.y') : $dt_date->format('Y-m-d');
//		return ($mode === "human") ? $dt_date->format(get_option('date_format')) : $dt_date->format('Y-m-d');
	} else {
		if (WP_DEBUG === true) { error_log("Got a wrong date format. (in miniplan_date_format)"); }
				apply_filters('debug', "Got a wrong date format. (in miniplan_date_format)");
		return $strdate;
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
