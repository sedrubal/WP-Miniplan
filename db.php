<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

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
                title tinytext NOT NULL,
                attendance tinytext DEFAULT '',
                notification tinytext DEFAULT '',
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
 * @param string $title: the title of the new miniplan
 * @param string $text: the text of the new miniplan
 * @param string $attendance: the names for attendance for this miniplan
 * @param string $notification: notifications for this miniplan
 * @param string $beginning: the start date for the new miniplan
 * @param string $until: the last date of the new miniplan
 */
function miniplan_add_new( $feed_id, $title, $text, $attendance, $notification, $beginning, $until ) {
	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->insert(
			$table_name,
			[
				'feed_id' 	=> $feed_id,
				'beginning' 	=> miniplan_date_format($beginning, "sql"),
				'until' 	=> miniplan_date_format($until, "sql"),
				'title' 	=> $title,
				'text' 		=> $text,
				'attendance' 	=> $attendance,
				'notification' 	=> $notification
			],
			['%d' , '%s', '%s', '%s', '%s', '%s', '%s']
	);
}

/**
 * Edits an existing miniplan in the database
 * @param int $mpl_id: the id of the miniplan
 * @param int $feed_id: the id of the current miniplan feed (int)
 * @param string $title: the title of the new miniplan
 * @param string $text: the text of the new miniplan
 * @param string $attendance: the names for attendance for this miniplan
 * @param string $notification: notifications for this miniplan
 * @param string $beginning: the start date for the new miniplan
 * @param string $until: the last date of the new miniplan
 */
function miniplan_edit_existing($mpl_id, $feed_id, $title, $text, $attendance, $notification, $beginning, $until) {

	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->update(
			$table_name,
			[
				'feed_id' 	=> $feed_id,
				'beginning' 	=> miniplan_date_format($beginning, "sql"),
				'until' 	=> miniplan_date_format($until, "sql"),
				'title' 	=> $title,
				'text' 		=> $text,
				'attendance' 	=> $attendance,
				'notification' 	=> $notification
			],
			['id' => $mpl_id ],
			['%d' , '%s', '%s', '%s', '%s', '%s', '%s']
			['%d']
	);
}

/**
 * Deletes an existing miniplan in the database
 * @param int $mpl_id: the id of the miniplan
 */
function miniplan_delete_existing($mpl_id) {

	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->delete(
			$table_name,
			['id' => $mpl_id ],
			['%d']
	);
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
