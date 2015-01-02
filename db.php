<?php

defined('ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * the database things
 */

/**
 * Adds a new miniplan to the database
 * @param int $feed_id: the id of the current miniplan feed (int)
 * @param string $title: the title of the new miniplan
 * @param string $text: the text of the new miniplan
 * @param string $beginning: the start date for the new miniplan
 * @param string $until: the last date of the new miniplan
 */
function miniplan_add_new( $feed_id, $title, $text, $beginning, $until ) {
	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->insert(
			$table_name,
			[
				'feed_id' 	=> $feed_id,
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
 * @param int $feed_id: the id of the current miniplan feed (int)
 * @param string $title: the title of the new miniplan
 * @param string $text: the text of the new miniplan
 * @param string $beginning: the start date for the new miniplan
 * @param string $until: the last date of the new miniplan
 */
function miniplan_edit_existing($mpl_id, $feed_id, $title, $text, $beginning, $until) {

	global $wpdb;
        $table_name = $wpdb->prefix . 'miniplan';
	$wpdb->update(
			$table_name,
			[
				'feed_id' 	=> $feed_id,
				'beginning' 	=> miniplan_date_format($beginning, "sql"),
				'until' 	=> miniplan_date_format($until, "sql"),
				'title' 	=> $title,
				'text' 		=> $text
            		],
			['id' => $mpl_id ],
			['%d' , '%s', '%s', '%s', '%s'],
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
