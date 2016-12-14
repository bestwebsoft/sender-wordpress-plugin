<?php
/**
* Includes deprecated functions
 */

/**
 * Rewriting 'date_create' type and data in base to datetime type
 * @deprecated since 1.1.8
 * @todo remove after 01.04.2017
*/
if ( ! function_exists( 'sndr_check_date_create_type' ) ) {
	function sndr_check_date_create_type() {
		global $wpdb;
		/* change column`s data type 'date_create' from table 'sndr_mail_send' */
		$data_type = $wpdb->get_row(
			"DESCRIBE `" . $wpdb->prefix . "sndr_mail_send` `date_create`",
			ARRAY_A
		);
		if ( $data_type["Type"] != 'datetime' ) {
			$letter_data = $wpdb->get_results( "SELECT `mail_send_id`, `date_create` FROM `" . $wpdb->prefix . "sndr_mail_send`;", ARRAY_A );
			$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` CHANGE `date_create` `date_create` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';" );
			/* rewriting data */
			foreach ( $letter_data as $letter ) {
				$wpdb->update(
					$wpdb->prefix . 'sndr_mail_send',
					array( 'date_create'  => date( 'Y-m-d H:i:s', $letter['date_create'] ) ),
					array( 'mail_send_id' => $letter['mail_send_id'] )
				);
			}
		}
	}
}