<?php
/**
* Includes deprecated functions
 * @deprecated since 1.1.8
 * @todo remove after 01.04.2017
 */

/* Rewriting 'date_create' type and data in base to datetime type */
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

/**
* @since 1.1.5 - deprecated (start)
* @todo delete after 01.10.2016
*/
if ( ! function_exists( 'sndr_check_smtp' ) ) {
	function sndr_check_smtp() {
		global $sndr_options;
		if ( $sndr_options['sndr_method'] == 'smtp' ) {
			$sndr_options['sndr_method'] = 'wp_mail';
			$sndr_options['sndr_smtp_warning'] = 1;
		}
	}
}

if ( ! function_exists( 'sndr_unset_smtp_warning' ) ) {
	function sndr_unset_smtp_warning() {
		global $sndr_options;
		if ( isset( $sndr_options['sndr_smtp_warning'] ) ) {
			unset( $sndr_options['sndr_smtp_warning'] );
			unset( $sndr_options['sndr_smtp_settings'] );
		}
	}
}

if ( ! function_exists( 'sndr_display_smtp_warning' ) ) {
	function sndr_display_smtp_warning() {
		global $sndr_options;
		if ( isset( $sndr_options['sndr_smtp_warning'] ) ) {
			if ( array_key_exists( 'bws-smtp/bws-smtp.php', $plugins_list ) ) {
				if ( is_plugin_active( 'bws-smtp/bws-smtp.php' ) ) {
					$bws_smtp_link = '<a href="admin.php?page=bwssmtp_settings">' . __( 'use', 'sender' ) . " " . 'SMTP by BestWebSoft</a>';
				} else {
					$bws_smtp_link = '<a href="' . get_bloginfo("url") . '/wp-admin/plugins.php">' . __( 'activate', 'sender' ) . " " . 'SMTP by BestWebSoft</a>';
				}
			} else {
				$bws_smtp_link = '<a href="http://bestwebsoft.com/products/smtp/?k=5cd69f5e4557344a7d1f1977981cbc52&pn=114&v=' . $sndr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'download', 'sender' ) . " " . 'SMTP by BestWebSoft</a>';
			}
			$smtp_setting = '';
			$smtp_settings = $sndr_options['sndr_smtp_settings'];
			foreach ( $smtp_settings as $key => $value ) {
				$smtp_setting .= $key . ' - ' . $value . '; ';
			} ?>
			<div class="error below-h2">
				<p><strong><?php echo __( "In current plugin version, there is an opportunity to use SMTP method from old plugin settings. If you're using this method for sending e-mails, please", 'sender' ) . ' ' . $bws_smtp_link . ' ' . __( 'and configure it in accordance with your settings', 'sender' ) . ' ( ' . $smtp_setting . ' ) ';?></strong></p>
			</div>
		<?php }
	}
}
/* deprecated (end) */