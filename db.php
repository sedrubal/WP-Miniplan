<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");
//in_array(strtolower(array_shift(wp_get_current_user()->roles)), get_option('miniplan_privileged_roles')) or wp_die('You are not privileged to do this!');


/**
 * the database things
 */

/**
 * creates the database for this plugin
 */
function miniplan_install_db() {

    global $wpdb;
    global $miniplan_db_version;

    $table_name = $wpdb->prefix . 'miniplan';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        feed_id tinyint DEFAULT '1' NOT NULL,
        beginning datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        until datetime DEFAULT '0000-00-07 00:00:00' NOT NULL,
        attendance tinytext DEFAULT '',
        notification text DEFAULT '',
        text text NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( 'miniplan_db_version', $miniplan_db_version );
}

/**
 * Adds a new miniplan to the database
 * @param int $feed_id: the id of the current miniplan feed (int)
 * @param string $text: the text of the new miniplan
 * @param string $attendance: the names for attendance for this miniplan
 * @param string $notification: notifications for this miniplan
 * @param DateTime $beginning: the start date for the new miniplan
 * @param DateTime $until: the last date of the new miniplan
 * @return: Error message as string or null on success.
 */
function miniplan_add_new( $feed_id, $text, $attendance, $notification, $beginning, $until ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'miniplan';
    $inserted = $wpdb->insert(
        $table_name,
        [
            'feed_id' 	=> $feed_id,
            'beginning' 	=> miniplan_date_format($beginning, "sql"),
            'until' 	=> miniplan_date_format($until, "sql"),
            'text' 		=> $text,
            'attendance' 	=> $attendance,
            'notification' 	=> $notification
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s']
    );
    if ( $inserted === 1 ) {
        return null;
    } else if ( $inserted === false ) {
        return "Miniplan konnte nicht erstellt werden. Bitte benachrichtige den Administrator der Website.";
    } else if ( $inserted > 1 ) {
        // According to the docs, this should not happen.
        return "Ohje. Eigentlich hätte nur ein Miniplan erstellt werden sollen, aber aus komischen Gründen wurden " . $inserted . " Pläne erstellt. Bitte benachrichtige den Administrator der Website.";
    } else {
        return "Ein unbekannter Fehler ist aufgetreten. Bitte benachrichtige den Administrator der Website.";
    }
}

/**
 * Edits an existing miniplan in the database
 * @param int $mpl_id: the id of the miniplan
 * @param int $feed_id: the id of the current miniplan feed (int)
 * @param string $text: the text of the new miniplan
 * @param string $attendance: the names for attendance for this miniplan
 * @param string $notification: notifications for this miniplan
 * @param DateTime $beginning: the start date for the new miniplan
 * @param DateTime $until: the last date of the new miniplan
 * @return: Error message as string or null on success.
 */
function miniplan_edit_existing($mpl_id, $feed_id, $text, $attendance, $notification, $beginning, $until) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'miniplan';
    $updated = $wpdb->update(
        $table_name,
        [
            'feed_id' 	=> $feed_id,
            'beginning' 	=> miniplan_date_format($beginning, "sql"),
            'until' 	=> miniplan_date_format($until, "sql"),
            'text' 		=> $text,
            'attendance' 	=> $attendance,
            'notification' 	=> $notification
        ],
        ['id' => $mpl_id ],
        ['%d', '%s', '%s', '%s', '%s', '%s']
        ['%d']
    );
    if ( $updated === 1 ) {
        return null;
    } else if ( $updated === 0 ) {
        return "Es konnte kein passender Datensatz mit der ID" . $mpl_id . " gefunden und aktualisiert werden.";
    } else if ( $updated === false ) {
        return "Miniplan konnte nicht aktualisiert werden. Bitte benachrichtige den Administrator der Website.";
    } else if ( $updated > 1 ) {
        // This should not happen as mpl_id should be unique.
        return "Ohje. Eigentlich hätte nur ein Miniplan aktualisiert werden sollen, aber aus komischen Gründen wurden " . $updated . " Pläne verändert. Bitte benachrichtige den Administrator der Website.";
    } else {
        return "Ein unbekannter Fehler ist aufgetreten. Bitte benachrichtige den Administrator der Website.";
    }
}

/**
 * Deletes an existing miniplan in the database
 * @param int $mpl_id: the id of the miniplan
 * @return: Error message as string or null on success.
 */
function miniplan_delete_existing($mpl_id) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'miniplan';
    $deleted = $wpdb->delete(
        $table_name,
        ['id' => $mpl_id ],
        ['%d']
    );
    if ( $deleted === 1 ) {
        return null;
    } else if ( $deleted === 0 ) {
        return "Es konnte kein passender Datensatz mit der ID" . $mpl_id . " gefunden und gelöscht werden. Vielleicht hat ihn gerade jemand anderes gelöscht?";
    } else if ( $deleted === false ) {
        return "Miniplan konnte nicht gelöscht werden. Bitte benachrichtige den Administrator der Website.";
    } else if ( $deleted > 1 ) {
        // This should not happen as mpl_id should be unique.
        return "Ohje. Eigentlich hätte nur ein Miniplan gelöscht werden sollen, aber aus komischen Gründen wurden " . $deleted . " Pläne gelöscht. Bitte benachrichtige den Administrator der Website.";
    } else {
        return "Ein unbekannter Fehler ist aufgetreten. Bitte benachrichtige den Administrator der Website.";
    }
}

/**
 * drops the database for this plugin
 */
function miniplan_drop_db() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'miniplan';

    $wpdb->query( "DROP TABLE IF EXISTS " . $table_name );

    delete_option( 'miniplan_db_version', $miniplan_db_version );
}

// vi: sw=8 ts=8 sts=8 et colorcolumn=100
