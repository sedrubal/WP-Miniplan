<?php

defined('MINIPLAN_ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");


/**
 * the database things
 */
class miniplan_db
{

    const db_version = '0.0.4';

	/**
	 * creates the database for this plugin
	 */
	public static function install()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'miniplan';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				feed_id tinyint DEFAULT '1' NOT NULL,
						beginning datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
						until datetime DEFAULT '0000-00-07 00:00:00' NOT NULL,
				attendance tinytext DEFAULT '',
				notification tinytext DEFAULT '',
				text text NOT NULL,
				UNIQUE KEY id (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('miniplan_db_version', self::db_version);
	}

    /**
     * checks, if the database tables need an upgrade
     */
    function check_update() {
        if ( get_site_option( 'miniplan_db_version' ) != self::db_version ) {
            self::install();
        }
    }

	/**
	 * Adds a new miniplan to the database
	 * @param int $feed_id : the id of the current miniplan feed (int)
	 * @param string $text : the text of the new miniplan
	 * @param string $attendance : the names for attendance for this miniplan
	 * @param string $notification : notifications for this miniplan
	 * @param DateTime $beginning : the start date for the new miniplan
	 * @param DateTime $until : the last date of the new miniplan
	 */
	public static function add_new_miniplan($feed_id, $text, $attendance, $notification, $beginning, $until)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'miniplan';
		$wpdb->insert(
			$table_name,
			[
				'feed_id' => $feed_id,
				'beginning' => miniplan_date_format($beginning, "sql"),
				'until' => miniplan_date_format($until, "sql"),
				'text' => $text,
				'attendance' => $attendance,
				'notification' => $notification
			],
			['%d', '%s', '%s', '%s', '%s', '%s']
		);
	}

	/**
	 * Edits an existing miniplan in the database
	 * @param int $mpl_id : the id of the miniplan
	 * @param int $feed_id : the id of the current miniplan feed (int)
	 * @param string $text : the text of the new miniplan
	 * @param string $attendance : the names for attendance for this miniplan
	 * @param string $notification : notifications for this miniplan
	 * @param DateTime $beginning : the start date for the new miniplan
	 * @param DateTime $until : the last date of the new miniplan
	 */
	public static function edit_existing_miniplan($mpl_id, $feed_id, $text, $attendance, $notification, $beginning, $until)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'miniplan';
		$wpdb->update(
			$table_name,
			[
				'feed_id' => $feed_id,
				'beginning' => miniplan_date_format($beginning, "sql"),
				'until' => miniplan_date_format($until, "sql"),
				'text' => $text,
				'attendance' => $attendance,
				'notification' => $notification
			],
			['id' => $mpl_id],
			['%d', '%s', '%s', '%s', '%s', '%s'],
			['%d']
		);
	}

	/**
	 * Deletes an existing miniplan in the database
	 * @param int $mpl_id : the id of the miniplan
	 */
	public static function delete_existing_miniplan($mpl_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'miniplan';
		$wpdb->delete(
			$table_name,
			['id' => $mpl_id],
			['%d']
		);
	}

	/**
	 * drops the database tables for this plugin
	 */
	public static function drop_tables()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'miniplan';

		$wpdb->query( "DROP TABLE IF EXISTS `" . $table_name . "`" );

		delete_option( 'miniplan_db_version', self::db_version );
	}


}