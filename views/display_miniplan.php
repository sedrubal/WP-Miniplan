<?php
/**
 * Displays the miniplan view (where you can see the miniplan as user)
 */

defined('MINIPLAN_ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * prints the miniplan things
 * @param array $atts: the Wordpress tag attributes
 * @return string: the miniplan with (admin)forms
 */
function display_miniplan( $atts ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'miniplan';

    $req_mpl = get_query_var( "miniplan", "latest" ); //$requested miniplan: it should be the req. mpl id, or "latest" for the latest mpl, or "-1" to show tha latest, but handle this seperately, when editing
    if ( !is_numeric($req_mpl)) { $req_mpl = "latest"; }

    $mpl_dates = $wpdb->get_results( 'SELECT id,beginning,until FROM `' . $table_name . '` WHERE feed_id = \'' . intval($atts["id"]) . '\' ORDER BY beginning DESC', OBJECT ); //get available plans from db (for dropdown)
    //create dropdown
    $ret = '<div id="miniplan" class="miniplan"><form action="' . $_SERVER['REQUEST_URI'] . '#miniplan" method="get">
		Datum: <select onchange="this.form.submit()" name="miniplan" required>
			<option value="latest" ' . (($req_mpl === 'latest' || $req_mpl === '-1') ? ' selected' : '') . ' >aktueller Plan</option>';
    // options from db
    foreach ($mpl_dates as $d) {
        $ret .= "<option value='" . $d->id . "'" . (($req_mpl === $d->id) ? " selected>" : ">") . miniplan_date_format($d->beginning) . " - "  . miniplan_date_format($d->until) . "</option>";
    }
    $ret .="</select>
		<noscript><button type='submit'>aktualisieren</button></noscript>
	</form>";

    //query selected miniplan from db
    $results = null;
    if ($req_mpl === "latest" || $req_mpl === "-1") {
        //get latest date until today
        $mostRecent= null;
        $now = new DateTime('Now');
        foreach($mpl_dates as $date){
            $curDate = new DateTime($date->beginning);
            if ($curDate > $mostRecent && $curDate < $now) {
                $mostRecent = $curDate;
            }
        }

        $results = $wpdb->get_results( "SELECT * FROM `" . $table_name .
            "` WHERE feed_id = '" . intval($atts["id"]) .
            "' AND DATE_FORMAT(beginning, \"%Y-%m-%d\") = DATE_FORMAT('" . miniplan_date_format($mostRecent, 'sql') . "', \"%Y-%m-%d\") ORDER BY beginning DESC LIMIT 1;", OBJECT );
    } else if (is_numeric($req_mpl)) {
        $results = $wpdb->get_results( "SELECT * FROM `" . $table_name . "` WHERE id = '" . intval($req_mpl) . "' ORDER BY beginning LIMIT 1", OBJECT );
    }
    if ($results === null || $results == "" || empty($results)) {
        if (WP_DEBUG === true) { error_log("Something went wrong, while fetching the miniplan!"); }
        apply_filters('debug', "Something went wrong, while fetching the miniplan!");
        $ret .= miniplan_message("Der Miniplan ist entweder nicht verfügbar oder kann aufgrund eines internen Fehlers nicht angezeigt werden.", "error") . '</div>';
        $ret .= print_miniplan_admin_form( intval($atts["id"]), null );
    } else {
        //display miniplan
        $ret .= '<h3>Miniplan - gültig vom ' . miniplan_date_format($results[0]->beginning) . ' bis zum ' . miniplan_date_format($results[0]->until) . '</h3>
					<!--[if IE 6]>' . miniplan_message( 'Dieser Browser wird nicht unterstützt.' , 'error' ) . '<![endif]-->' .
            ((strlen($results[0]->attendance) > 0) ? '<div id="attendance"><strong><p>Bereitschaft: ' . $results[0]->attendance . '</strong></p></div>' : '') .
            '<pre id="miniplan_content">' . $results[0]->text . '</pre>' .
            ((strlen($results[0]->notification) > 0) ? ('</br>' . miniplan_message($results[0]->notification, 'notification')) : '') . '</div>';
        $ret .= print_miniplan_admin_form( intval($atts["id"]) , $results[0]);
    }
    return $ret;
}