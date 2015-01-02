<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * the database things
 */

/**
 * Adds a new miniplan to the database
 * @param int $feedid: the id of the current miniplan feed (int)
 * @param string $title: the title of the new miniplan
 * @param string $text: the text of the new miniplan
 * @param string $beginning: the start date for the new miniplan
 * @param string $until: the last date of the new miniplan
 */
function miniplan_add_new( $feedid, $title, $text, $beginning, $until ) {
	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->insert(
			$table_name,
			[
				'feedid' 	=> $feedid,
				'beginning' 	=> miniplan_date_format($beginning, "sql"),
				'until' 	=> miniplan_date_format($until, "sql"),
				'title' 	=> $title,
				'text' 		=> $text
            ],
			['%d' , '%s', '%s', '%s', '%s']
	);
}

/**
 * Edits an existing miniplan in the database
 * @param int $mpl_id: the id of the miniplan
 * @param int $feedid: the id of the current miniplan feed (int)
 * @param stdClass $mpl: a standard class containing the new values as variables
 */
function miniplan_edit_existing($mpl_id, $feed_id, $title, $text, $beginning, $until) {

	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->update(
			$table_name,
			[
				'feedid' 	=> $feed_id,
				'beginning' 	=> miniplan_date_format($beginning, "sql"),
				'until' 	=> miniplan_date_format($until, "sql"),
				'title' 	=> $title,
				'text' 		=> $text
            		],
			['id' => intval($mpl_id) ],
			['%d' , '%s', '%s', '%s', '%s'],
			['%d']
	);
}
