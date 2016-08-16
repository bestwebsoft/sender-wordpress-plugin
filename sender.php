<?php
/*
Plugin Name: Sender by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/sender/
Description: Send bulk email messages to WordPress users. Custom templates, advanced settings and detailed reports.
Author: BestWebSoft
Text Domain: sender
Domain Path: /languages
Version: 1.1.3
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2016  BestWebSoft  ( http://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* Add menu and submenu.
* @return void
*/

if ( ! function_exists( 'sndr_admin_default_setup' ) ) {
	function sndr_admin_default_setup() {
		global $bstwbsftwppdtplgns_options, $bstwbsftwppdtplgns_added_menu, $submenu;
		if ( is_multisite()  && ! is_network_admin() ) {
			return;
		}
		$icon_path = plugins_url( "images/plugin_icon_38.png",  __FILE__ );
		bws_general_menu();

		$settings = add_submenu_page( 'bws_panel', 'Sender', 'Sender', 'manage_options', 'sndr_settings', 'sndr_admin_settings_content' );
		$sndr_send_user = add_menu_page( 'Sender', 'Sender', 'manage_options', 'sndr_send_user', 'sndr_admin_mail_send', $icon_path, '57.1' );
		$hook = add_submenu_page( 'sndr_send_user', __( 'Reports', 'sender' ), __( 'Reports', 'sender' ), 'manage_options', 'view_mail_send', 'sndr_mail_view' );

		/* pro pages */
		add_submenu_page( 'sndr_send_user', __( 'New Mailout', 'sender' ), __( 'New Mailout', 'sender' ), 'manage_options', 'sndrpr_create_mailout', 'sndr_create_mailout' );
		add_submenu_page( 'sndr_send_user', __( 'Letters', 'sender' ), __( 'Letters', 'sender' ), 'manage_options', 'sndrpr_letters_list', 'sndr_letters_list' );
		add_submenu_page( 'sndr_send_user', __( 'Mailings Lists', 'sender' ), __( 'Mailings Lists', 'sender' ), 'manage_options', 'sndrpr_distribution_list', 'sndr_distribution_list' );
		add_submenu_page( 'sndr_send_user', __( 'Letters Templates', 'sender' ), __( 'Letters Templates', 'sender' ), 'manage_options', 'sndrpr_letter_templates', 'sndr_letter_templates' );
		add_submenu_page( 'sndr_send_user', __( 'Priorities', 'sender' ), __( 'Priorities', 'sender' ), 'manage_options', 'sndrpr_priorities', 'sndr_priorities' );

		$admin_url = is_multisite() ? network_admin_url( 'admin.php?page=sndr_settings' ) : admin_url( 'admin.php?page=sndr_settings' );

		if ( isset( $submenu['sndr_send_user'] ) )
			$submenu['sndr_send_user'][] = array( __( 'Settings', 'sender' ), 'manage_options', $admin_url );

		add_action( "load-$settings", 'sndr_add_tabs' );
		add_action( "load-$sndr_send_user", 'sndr_add_tabs' );
		add_action( "load-$hook", 'sndr_screen_options' );
	}
}

/**
 * Internationalization 
 * @return void
 */
if ( ! function_exists( 'sndr_plugins_loaded' ) ) {
	function sndr_plugins_loaded() {
		load_plugin_textdomain( 'sender', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * Plugin functions for init
 * @return void
 */
if ( ! function_exists ( 'sndr_init' ) ) {
	function sndr_init() {
		global $sndr_plugin_info;
		
		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $sndr_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$sndr_plugin_info = get_plugin_data( dirname(__FILE__) . '/sender.php' );
		}

		/* check WordPress version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $sndr_plugin_info, '3.8' );
	}
}

/**
 * Plugin functions for admin init
 * @return void
 */
if ( ! function_exists ( 'sndr_admin_init' ) ) {
	function sndr_admin_init() {
		global $bws_plugin_info, $sndr_plugin_info;

		if ( ! $sndr_plugin_info )
			$sndr_plugin_info = get_plugin_data( __FILE__ );

		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '114', 'version' => $sndr_plugin_info["Version"] );

		if ( isset( $_REQUEST['page'] ) && ( 'sndr_send_user' == $_REQUEST['page'] || 'view_mail_send' == $_REQUEST['page'] || 'sndr_settings' == $_REQUEST['page'] ) ) {
			/* register plugin settings */
			sndr_register_settings();
		}
	}
}


/**
 * Register settings function
 * @return void
 */
if ( ! function_exists( 'sndr_register_settings' ) ) {
	function sndr_register_settings() {
		global $wpdb, $sndr_options, $sndr_options_default, $sndr_plugin_info;
		$sndr_db_version = '0.3';

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		$sndr_options_default = array(
			'plugin_option_version'		=>	$sndr_plugin_info["Version"],
			'plugin_db_version'			=>	$sndr_db_version,
			'sndr_run_time'				=>	1,
			'sndr_send_count'			=>	2,
			'sndr_from_custom_name'		=>	get_bloginfo( 'name' ), /* custom name in field 'From' */
			'sndr_from_email'			=>	$from_email,  /* wp email */
			'sndr_method'				=>	'wp_mail',
			'display_settings_notice'	=>	1,
			'first_install'				=>	strtotime( "now" ),
			'suggest_feature_banner'	=>	1
		);

		/* install the default plugin options */
		if ( is_multisite() ) {
			if ( ! get_site_option( 'sndr_options' ) )
				add_site_option( 'sndr_options', $sndr_options_default );
		} else {
			if ( ! get_option( 'sndr_options' ) )
				add_option( 'sndr_options', $sndr_options_default );
		}

		/* get plugin options from the database */
		$sndr_options = is_multisite() ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );

		/**
		 * update pugin database and options
		 * this code is needed to update plugin from old versions of plugin 0.1 
		 */
		if ( ! isset( $sndr_options['plugin_db_version'] ) || $sndr_options['plugin_db_version'] != $sndr_db_version ) {
			/* update plugin database */
			$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_users` LIKE 'status';" );

			if ( 0 == $colum_exists )
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_users` CHANGE `ship` `status` INT( 1 ) NOT NULL DEFAULT '0';" );

			$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_mail_send` LIKE 'mail_status';" );

			if ( 0 == $colum_exists ) {
				$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_mail_send` LIKE 'done'" );
				if ( ! empty( $colum_exists ) )
					$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` CHANGE `done` `mail_status` INT( 1 ) NOT NULL DEFAULT '0'" );
				else
					$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` ADD `mail_status` INT( 1 ) NOT NULL DEFAULT '0'" );
			}

			/* added "remote_delivery" column */
			$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_mail_send` LIKE 'remote_delivery';" );

			if ( 0 == $colum_exists )
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` ADD `remote_delivery` INT(1) NOT NULL DEFAULT '0';" );

			$sndr_options['plugin_db_version'] = $sndr_db_version;

			$update_option = true;
		}

		if ( ! isset( $sndr_options['plugin_option_version'] ) || $sndr_options['plugin_option_version'] != $sndr_plugin_info["Version"] ) {
						
			/* array merge incase new version of plugin has added new options */
			$sndr_options = array_merge( $sndr_options_default, $sndr_options );
			$sndr_options['plugin_option_version'] = $sndr_plugin_info["Version"];

			/**
			* @since 1.1.1 - deprecated (start)
			* @todo delete after 1.10.2016
			*/
			if ( $sndr_options['sndr_method'] == 'smtp' ) {
				$sndr_options['sndr_method'] = 'wp_mail';
				$sndr_options['sndr_smtp_warning'] = 1;
			} /* deprecated (end) */

			if ( empty( $sndr_options['sndr_from_email'] ) || ! is_email( $sndr_options['sndr_from_email'] ) )
				$sndr_options['sndr_from_email'] = $from_email;

			if ( empty( $sndr_options['sndr_from_custom_name'] ) )
				$sndr_options['sndr_from_custom_name'] = get_bloginfo( 'name' );

			/* show pro features */
			$sndr_options['hide_premium_options'] = array();

			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			if ( is_multisite() )
				update_site_option( 'sndr_options', $sndr_options );
			else
				update_option( 'sndr_options', $sndr_options );
		}
	}
}


/**
 * Add action links on plugin page in to Plugin Name block
 * @param $links array() action links
 * @param $file  string  relative path to pugin "sender/sender.php"
 * @return $links array() action links
 */
if ( ! function_exists ( 'sndr_plugin_action_links' ) ) {
	function sndr_plugin_action_links( $links, $file ) {
		if ( ( is_multisite() && is_network_admin() ) || ( ! is_multisite() && is_admin() ) ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename( __FILE__ );
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=sndr_settings">' . __( 'Settings', 'sender' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/**
 * Add action links on plugin page in to Plugin Description block
 * @param $links array() action links
 * @param $file  string  relative path to pugin "sender/sender.php"
 * @return $links array() action links
 */
if ( ! function_exists ( 'sndr_register_plugin_links' ) ) {
	function sndr_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ( is_multisite() && is_network_admin() ) || ( ! is_multisite() && is_admin() ) ) {
				$links[] = '<a href="admin.php?page=sndr_settings">' . __( 'Settings', 'sender' ) . '</a>';
			}
			$links[] = '<a href="http://wordpress.org/plugins/sender/faq/" target="_blank">' . __( 'FAQ', 'sender' ) . '</a>';
			$links[] = '<a href="http://support.bestwebsoft.com" target="_blank">' . __( 'Support', 'sender' ) . '</a>';
		}
		return $links;
	}
}

/**
* Performed at activation.
* @return void
*/
if ( ! function_exists( 'sndr_send_activate' ) ) {
	function sndr_send_activate() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$mail = 
			"CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "sndr_mail_send` (
			`mail_send_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			`subject` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			`body` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			`date_create` INT UNSIGNED NOT NULL ,
			`mail_status` INT( 1 ) NOT NULL DEFAULT '0' ,
			`remote_delivery` INT( 1 ) NOT NULL DEFAULT '0' ,
			PRIMARY KEY ( `mail_send_id` )
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $mail );

		$mail_users = 
			"CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "sndr_users` (
			`mail_users_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			`id_user` INT NOT NULL ,
			`id_mail` INT UNSIGNED NOT NULL ,
			`status` INT( 1 ) NOT NULL DEFAULT '0',
			`view` INT( 1 ) NOT NULL DEFAULT '0',
			`try` INT( 1 ) NOT NULL DEFAULT '0',
			PRIMARY KEY ( `mail_users_id` )
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $mail_users );

		$users_create = 
			"CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "sndr_mail_users_info` (
			`mail_users_info_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			`id_user` INT NOT NULL ,
			`user_email` VARCHAR( 255 ) NOT NULL ,
			`user_display_name` VARCHAR( 255 ) NOT NULL ,
			`subscribe` INT( 1 ) NOT NULL DEFAULT '1',
			PRIMARY KEY ( `mail_users_info_id` )
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $users_create );

		/* copy data from wp_users */
		$sndr_users_info = $wpdb->query( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_users_info`;", ARRAY_A );
		if ( empty( $sndr_users_info ) ) {
			$wpdb->query( 
				"INSERT INTO `" . $wpdb->prefix . "sndr_mail_users_info` ( `id_user`, `user_display_name`, `user_email`, `subscribe` ) 
					( SELECT `ID`, `display_name`, `user_email`, 1 FROM `" . $wpdb->prefix . "users` );" 
			);
		} else { /* Add users data which were not inserted in plugin tables */
			if ( function_exists( 'sbscrbr_users_list' ) || function_exists( 'sbscrbrpr_users_list' ) ) { /* if Subscriber plugin already installed and activated */
				$wpdb->query( 
					"INSERT INTO `" . $wpdb->prefix . "sndr_mail_users_info` 
					( `id_user`, `user_display_name`, `user_email`, `subscribe`, `unsubscribe_code`, `subscribe_time` ) 
					( SELECT `ID`, `display_name`, `user_email`, 1, MD5(RAND()), " . time() . " FROM `" . $wpdb->prefix . "users` 
						WHERE `ID` NOT IN ( SELECT `id_user` FROM `" . $wpdb->prefix . "sndr_mail_users_info` ) 
					);"
				);
			} else {
				$wpdb->query(
					"INSERT INTO `" . $wpdb->prefix . "sndr_mail_users_info` 
					( `id_user`, `user_display_name`, `user_email`, `subscribe` ) 
					( SELECT `ID`, `display_name`, `user_email`, 1 FROM `" . $wpdb->prefix . "users` 
						WHERE `ID` NOT IN ( SELECT `id_user` FROM `" . $wpdb->prefix . "sndr_mail_users_info` ) 
					);"
				);
			}
		}
		/* after deactivating Pro */
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_mail_send` LIKE 'mail_status'" );
		if ( empty( $column_exists ) )
			$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` ADD `mail_status` INT( 1 ) NOT NULL DEFAULT '0'" );
		/* check if free sender mailouts with '0' sent status exist */
		$sndr_unsent_messages = $wpdb->get_col( "SELECT `mail_send_id` FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_status` = '0';" );
		if ( ! empty( $sndr_unsent_messages ) ) {
			$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_users` LIKE 'id_mailout';" );
			/* if PRO column exists - we don't touch letters of PRO plugin */
			$additional_condition = ( 0 == $colum_exists ) ? "" : " AND ( `id_mailout`=`id_mail` OR `id_mailout`=0 )";
			foreach ( $sndr_unsent_messages as $mail_send_id ) {
				/* check if sent users exist */
				$users_list_sent  = $wpdb->get_var(
					"SELECT COUNT( DISTINCT `id_user` ) FROM `" . $wpdb->prefix . "sndr_users` LEFT JOIN `" . $wpdb->prefix . "sndr_mail_send` ON `" . $wpdb->prefix . "sndr_users`.`id_mail`=`" . $wpdb->prefix . "sndr_mail_send`.`mail_send_id` WHERE `id_mail`=" . $mail_send_id . $additional_condition . " AND `status`='1';"
				);
				if ( ! empty( $users_list_sent) && 0 < $users_list_sent ) {
					/* check if not sent users exist */
					$users_list_not_sent  = $wpdb->get_var( 
						"SELECT COUNT( DISTINCT `id_user` ) FROM `" . $wpdb->prefix . "sndr_users` LEFT JOIN `" . $wpdb->prefix . "sndr_mail_send` ON `" . $wpdb->prefix . "sndr_users`.`id_mail`=`" . $wpdb->prefix . "sndr_mail_send`.`mail_send_id` WHERE `id_mail`=" . $mail_send_id . $additional_condition . " AND `status`='0';"
					);
					if ( empty( $users_list_not_sent ) || 0 == $users_list_not_sent ) {
						/* try to change status to '1' if no unsent users left and sent users exist */
						$wpdb->query( "UPDATE `" . $wpdb->prefix . "sndr_mail_send` SET `mail_status`='1' WHERE `mail_send_id`=" . $mail_send_id . ";" );
					}
				}
			}
		}
		/* change column`s data type 'date_create' from table 'sndr_mail_send' */
		$data_type = $wpdb->get_row( 
			"DESCRIBE `" . $wpdb->prefix . "sndr_mail_send` `date_create`",
			ARRAY_A
		);
		if ( $data_type["Type"] == 'datetime' ) {
			$letter_data = $wpdb->get_results( "SELECT `mail_send_id`, `date_create` FROM `" . $wpdb->prefix . "sndr_mail_send`;", ARRAY_A );
			$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` CHANGE `date_create` `date_create` INT UNSIGNED NOT NULL;" );
			/* rewriting data */
			foreach ( $letter_data as $letter ) {
				$wpdb->update(
					$wpdb->prefix . 'sndr_mail_send', 
					array( 'date_create'  => strtotime( $letter['date_create'] ) ),
					array( 'mail_send_id' => $letter['mail_send_id'] )
				);
			}
		}
	}
}

/**
 * Function to add plugin scripts
 * @return void
 */
if ( ! function_exists ( 'sndr_admin_head' ) ) {
	function sndr_admin_head() {
		wp_enqueue_style( 'sndr_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
		
		if ( isset( $_REQUEST['page'] ) && ( 'sndr_send_user' == $_REQUEST['page'] || 'view_mail_send' == $_REQUEST['page'] || 'sndr_settings' == $_REQUEST['page'] ) ) {
			$script_vars = array(
				'closeReport'   => __( 'Close Report', 'sender' ),
				'showReport'    => __( 'Show Report', 'sender' ),
				'emptyReport'   => __( "The data of this report can't be found.", 'sender' ),
				'badRequest'    => __( 'Error while sending request.', 'sender' ),
				'toLongMessage' => __( 'Are you sure that you want to enter such a large value?', 'sender' ),
				'sndr_ajax_nonce'  => wp_create_nonce( 'sndr_ajax_nonce_value' )
			);
			wp_enqueue_script( 'sndr_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'sndr_script', 'sndrScriptVars', $script_vars );
		}
	}
}

/**
 * View function the settings to send messages.
 * @return void
 */
if ( ! function_exists( 'sndr_admin_settings_content' ) ) {
	function sndr_admin_settings_content() {
		global $wp_version, $wpdb, $sndr_options, $sndr_options_default, $title, $sndr_plugin_info;
		$display_add_options = $message = $error = $notice = '';
		$plugin_basename =  plugin_basename( __FILE__ );

		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugins_list = get_plugins();
		
		if ( empty( $sndr_options ) ) {
			$sndr_options = ( is_multisite() ) ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );
		}

		$admin_list = $wpdb->get_results( 
			"SELECT DISTINCT `user_login` , `display_name` FROM `" . $wpdb->prefix . "users` 
				LEFT JOIN `" . $wpdb->prefix . "usermeta` ON `" . $wpdb->prefix . "usermeta`.`user_id` = `" . $wpdb->prefix . "users`.`ID` 
			WHERE `meta_value` LIKE  '%administrator%'",
			ARRAY_A
		);

		if ( isset( $_POST['sndr_form_submit'] ) && check_admin_referer( $plugin_basename, 'sndr_nonce_name' ) ) {
			if ( isset( $_POST['bws_hide_premium_options'] ) ) {
				$hide_result = bws_hide_premium_options( $sndr_options );
				$sndr_options = $hide_result['options'];
			}

			/* update settings to send messages */
			/* check value from "Interval for sending mail" option */
			if ( isset( $_POST['sndr_mail_run_time'] ) ) {
				if ( empty( $_POST['sndr_mail_run_time'] ) || 1 > intval( $_POST['sndr_mail_run_time'] ) || ! preg_match( '/^\d+$/', $_POST['sndr_mail_run_time'] ) ) {
					$sndr_options['sndr_run_time'] = '1';
				} else {
					if ( 360 < $_POST['sndr_mail_run_time'] ) {
						$message .= __( 'You may have entered too large a value in the "Interval for sending mail" option. Check please.', 'sender' ) . '<br/>';
					}
					$sndr_options['sndr_run_time'] = intval( $_POST['sndr_mail_run_time'] );
				}
				add_filter( 'cron_schedules', 'sndr_more_reccurences' );
			} else {
				$sndr_options['sndr_run_time'] = $sndr_options_default['sndr_run_time'];
			}
			/* check value from "Number of messages sent at one time" option */
			if ( isset( $_POST['sndr_mail_send_count'] ) ) {
				if ( empty( $_POST['sndr_mail_send_count'] ) || 1 > intval( $_POST['sndr_mail_send_count'] ) || ! preg_match( '/^\d+$/', $_POST['sndr_mail_send_count'] ) ) {
					$sndr_options['sndr_send_count'] = '1';
				} else {
					if ( 50 < $_POST['sndr_mail_send_count'] ) {
						$message .= __( 'You may have entered too large a value in the "Number of sent messages at one time" option. Check please.', 'sender' ) . '<br/>';
					}
					$sndr_options['sndr_send_count'] = intval( $_POST['sndr_mail_send_count'] );
				}
			} else {
				$sndr_options['sndr_send_count'] = $sndr_options_default['sndr_send_count'];
			}

			if ( isset( $_POST['sndr_from_email'] ) && is_email( trim( $_POST['sndr_from_email'] ) ) ) {
				if ( $sndr_options['sndr_from_email'] != trim( $_POST['sndr_from_email'] ) )
					$notice = __( "Email 'FROM' field option was changed, which may cause email messages being moved to the spam folder or email delivery failures.", 'sender' );
				$sndr_options['sndr_from_email']  = trim( $_POST['sndr_from_email'] );
			} else
				$sndr_options['sndr_from_email']  = $sndr_options_default['sndr_from_email'];

			$sndr_options['sndr_from_custom_name']  = isset( $_POST['sndr_from_custom_name'] ) ? stripslashes( esc_html( $_POST['sndr_from_custom_name'] ) ) : $sndr_options_default['sndr_from_custom_name'];
			
			if ( '' == $sndr_options['sndr_from_custom_name'] )
				$sndr_options['sndr_from_custom_name'] = $sndr_options_default['sndr_from_custom_name'];

			$sndr_options['sndr_method'] = $_POST['sndr_mail_method'] == 'mail' ? 'mail' : 'wp_mail';

			/**
			* @since 1.1.1 - deprecated (start)
			* @todo delete after 1.10.2016
			*/
			if ( isset( $sndr_options['sndr_smtp_warning'] ) ) {
				unset( $sndr_options['sndr_smtp_warning'] );
				unset( $sndr_options['sndr_smtp_settings'] );
			} /* deprecated (end) */
						
			if ( is_multisite() )
				update_site_option( 'sndr_options', $sndr_options );
			else
				update_option( 'sndr_options', $sndr_options );

			$message .= __( "Settings saved.", 'sender' );
		}

		$bws_hide_premium_options_check = bws_hide_premium_options_check( $sndr_options );

		/*## Add restore function */
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
			$sndr_options = $sndr_options_default;
			if ( is_multisite() )
				update_site_option( 'sndr_options', $sndr_options );
			else
				update_option( 'sndr_options', $sndr_options );
			$message = __( 'All plugin settings were restored.', 'sender' );
		}		
		/* end ##*/

		/* GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			$go_pro_result = bws_go_pro_tab_check( $plugin_basename, 'sndr_options', is_multisite() );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
			elseif ( ! empty( $go_pro_result['message'] ) )
				$message = $go_pro_result['message'];
		} ?>
		<div class="sndr-mail wrap" id="sndr-mail">
			<h1><?php _e( "Sender Settings", 'sender' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=sndr_settings"><?php _e( 'Settings', 'sender' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=sndr_settings&amp;action=go_pro"><?php _e( 'Go PRO', 'sender' ); ?></a>
			</h2>
			<?php
			/**
			* @since 1.1.1 - deprecated (start)
			* @todo delete after 1.10.2016
			*/
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
			<?php } /* deprecated (end) */ ?>
			<div class="updated fade below-h2" <?php if ( empty( $message ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! empty( $notice ) ) { ?>
				<div class="error below-h2"><p><strong><?php _e( 'Notice:', 'sender' ); ?></strong> <?php echo $notice; ?></p></div>
			<?php }
			if ( ! empty( $hide_result['message'] ) ) { ?>
				<div class="updated fade below-h2"><p><strong><?php echo $hide_result['message']; ?></strong></p></div>
			<?php }
			if ( ! isset( $_GET['action'] ) ) { 
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( $plugin_basename );
				} else { 
					bws_show_settings_notice(); ?>
					<form class="bws_form" method="post" action="admin.php?page=sndr_settings">
						<table id="sndr-settings-table" class="form-table">
							<tr valign="top">
								<th scope="row"><?php _e( 'Interval for sending mail', 'sender' ); ?></th>
								<td><input id="sndr_mail_run_time" name='sndr_mail_run_time' type='number' min='0' value='<?php echo $sndr_options['sndr_run_time']; ?>'> <?php _e( '(min)', 'sender' ); ?></td>
							</tr>
							<tr>
								<th><?php _e( 'Number of messages sent at one time', 'sender' ); ?></th>
								<td>
									<input id="sndr_mail_send_count" name='sndr_mail_send_count' type='number' min='1' value='<?php echo $sndr_options['sndr_send_count']; ?>'><br/>
									<span class="bws_info">
										<?php $number = floor( ( 60 / intval( $sndr_options['sndr_run_time'] ) ) * intval( $sndr_options['sndr_send_count'] ) );
										_e( 'maximum number of sent mails:', 'sender' );?>&nbsp;<span id="sndr-calculate"><?php echo $number; ?></span>&nbsp;<?php _e( 'per hour', 'sender' ); ?>.&nbsp;<br/><span id="sndr_calc_info"><?php _e( 'Please make sure that this number is smaller than max allowed number of sent mails from your hosting account.', 'sender' ); ?></span>
									</span>
								</td>
							</tr>
						</table>
						<?php if ( ! $bws_hide_premium_options_check ) { ?>
							<div class="bws_pro_version_bloc">
								<div class="bws_pro_version_table_bloc">
									<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'sender' ); ?>"></button>
									<div class="bws_table_bg"></div>
									<table class="form-table bws_pro_version">
										<tr valign="top">
											<th><?php _e( 'Attempts number', 'sender' ); ?></th>
											<td>
												<input disabled name='sndrpr_max_try_count' type='text' value=''><br/>
												<span class="bws_info">( <?php _e( 'Maximum number of attempts to send a message to a single user', 'sender' ); ?> )</span>
											</td>
										</tr>
										<tr valign="top">
											<th><?php _e( "Email in HTML format sending", 'sender' ); ?></th>
											<td><input disabled type="checkbox" name="sndrpr_html_email" value="1" /></td>
										</tr>
										<tr class="sndrpr-add-set" valign="top">
											<th><?php _e( 'Send email with confirmation', 'sender' ); ?></th>
											<td>
												<input disabled type='checkbox' name='sndrpr_confirm' value="1" />
												<br/>
												<span class="bws_info">( <?php _e( "This function works not on all mail servers.", 'sender' ); ?> )</span>
											</td>
										</tr>
										<tr valign="top">
											<th><?php _e( 'Display fonts list on "Edit letter" or "Edit Template" Pages', 'sender' ); ?></th>
											<td>
												<input disabled type='checkbox' name='sndrpr_display_fonts_list' value="1" />
												<br/>
												<span class="bws_info">( <?php _e( "Due to security reasons, the additional fonts may not be reflected on some postal services.", 'sender' ); ?> )</span>
											</td>
										</tr>	
										<tr valign="top">
											<th scope="row" colspan="2">
												* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'sender' ); ?>
											</th>
										</tr>
									</table>
								</div>
								<div class="bws_pro_version_tooltip">
									<div class="bws_info">
										<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
									</div>
									<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
									<div class="clear"></div>
								</div>
							</div>
						<?php } ?>
						<table class="form-table">
							<tr valign="top">
								<th scope="row" style="width:200px;"><?php _e( "'FROM' field", 'sender' ); ?></th>
								<td class="sndr_from">
									<div><?php _e( "Name", 'sender' ); ?></div>
									<div>
										<input type="text" maxlength='250' name="sndr_from_custom_name" value="<?php echo $sndr_options['sndr_from_custom_name']; ?>"/>
									</div>
								</td>
								<td>
									<div><?php _e( "Email", 'sender' ); ?></div>
									<div>
										<input type="text" maxlength='250' name="sndr_from_email" value="<?php echo $sndr_options['sndr_from_email']; ?>"/>
									</div>
									<span class="bws_info">(<?php _e( "If this option is changed, email messages may be moved to the spam folder or email delivery failures may occur.", 'sender' ); ?>)</span>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'What to use?', 'sender' ); ?></th>
								<td colspan="2">
									<fieldset>
										<label>
											<input type='radio' name='sndr_mail_method' value='wp_mail' <?php if ( $sndr_options['sndr_method'] == 'wp_mail' ) echo 'checked="checked"'; ?>/> 
											<?php _e( 'Wp-mail', 'sender' ); ?> <span class="bws_info">(<?php _e( 'You can use the wp_mail function for mailing', 'sender' ); ?>)</span>
										</label><br/>
										<label>
											<input type='radio' name='sndr_mail_method' value='mail' <?php if ( $sndr_options['sndr_method'] == 'mail' ) echo 'checked="checked"'; ?>/> 
											<?php _e( 'Mail', 'sender' ); ?> <span class="bws_info">(<?php _e( 'To send mail you can use the php mail function', 'sender' ); ?>)</span>
										</label><br/>
									</fieldset>
								</td>
							</tr>
						</table>
						<?php if ( ! $bws_hide_premium_options_check ) { ?>
							<div class="bws_pro_version_bloc">
								<div class="bws_pro_version_table_bloc">
									<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'sender' ); ?>"></button>
									<div class="bws_table_bg"></div>
									<table class="form-table bws_pro_version" >
										<tr class="sndrpr_new_post">
											<th><?php _e( 'Automatic mailout when publishing a new post', 'sender' ); ?></th>
											<td colspan="2" valign="middle">
												<input type="checkbox" name="sndrpr_automailout_new_post" value="1" disabled="disabled" /><br />
												<p>
													<select name="sndrpr_user_select" disabled="disabled"><option>user group</option></select>
													<?php _e( 'Choose a mailing list', 'sender' ); ?>
												</p>
												<p>
													<select name="sndrpr_templates_select" disabled="disabled"><option>letter</option></select>
													<?php _e( 'Choose a letter', 'sender' ); ?>
												</p>
												<p>
													<select name="sndrpr_priority_select" disabled="disabled"><option>letter priority</option></select>
													<?php _e( 'Select letter priority', 'sender' ); ?><br />
													<span class="bws_info"><?php  _e( 'Less number - higher priority', 'sender' ) ?></span>
												</p>
											</td>
										</tr>
										<tr valign="top" >
											<th scope="row" colspan="6">
												* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'sender' ); ?>
											</th>	
										</tr>
									</table>
									<div class="bws_pro_version_tooltip">
										<div class="bws_info">
											<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
										</div>
										<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
										<div class="clear"></div>
									</div>
								</div>
							</div>
						<?php }
						if ( false == sndr_check_subscriber_install() ) {
							echo '<p>' . __( 'If you want to allows your site visitors to subscribe for newsletters, coming from your website, use', 'sender' ) . ' <a href="http://bestwebsoft.com/products/subscriber/?k=b7d5819c3c7c615e5e9dc6f3c8edd7d1&pn=114&v=' . $sndr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Subscriber plugin</a> ' . __( 'that is an exclusive add-on for the Sender Plugin by BestWebSoft.', 'sender' ) . '</p>';
						} ?>
						<p class="submit">
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'sender' ) ?>" />
							<input type="hidden" name="sndr_form_submit" value="submit" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sndr_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				}
			} elseif ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
				bws_go_pro_tab_show( $bws_hide_premium_options_check, $sndr_plugin_info, $plugin_basename, 'sndr_settings', 'sndrpr_settings', 'sender-pro/sender-pro.php', 'sender', '9436d142212184502ae7f7af7183d0eb', '114', isset( $go_pro_result['pro_plugin_is_activated'] ) ); 
			}
			bws_plugin_reviews_block( $sndr_plugin_info['Name'], 'sender' ); ?>
		</div><!--  #sndr-mail .sndr-mail -->
	<?php }
}

/**
 * Function sending messages.
 * @return void
 */
if ( ! function_exists( 'sndr_admin_mail_send' ) ) {
	function sndr_admin_mail_send() {
		global $user, $wpdb, $title;
		$sndr_error = $sndr_message = '';
		$user_count_by_roles = 0;
		$roles = array();
		$add_condition       = ( function_exists( 'sbscrbr_users_list' ) || function_exists( 'sbscrbrpr_users_list' ) ) ? " AND `" . $wpdb->prefix . "sndr_mail_users_info`.`black_list`=0 AND `" . $wpdb->prefix . "sndr_mail_users_info`.`delete`=0 " : '';
		if ( is_multisite() ) {
			$users_roles_list = $wpdb->get_results(
				"SELECT `user_id`, `meta_value`,
					( SELECT COUNT( DISTINCT `user_id`) FROM `" . $wpdb->prefix . "usermeta` LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info` ON  `" . $wpdb->prefix . "usermeta`.`user_id`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user` WHERE `meta_key` LIKE '%capabilities%' AND `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . " ) AS `all` 
				FROM `" . $wpdb->prefix . "usermeta` 
				LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info` ON `" . $wpdb->prefix . "usermeta`.`user_id`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user`
				WHERE `meta_key` LIKE '%capabilities%' AND `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . " ORDER BY `meta_value`;", 
				ARRAY_A 
			);
			$user_roles = $roles;
			if ( empty( $users_roles_list ) ) {
				$all_count = 0;
			} else {
				$all_count  = $users_roles_list[0]['all']; /* all users count */
				foreach( $users_roles_list as $key => $role_data ) {
					$role = key( unserialize( $role_data['meta_value'] ) ); /* get name of role */
					if ( ! array_key_exists( $role, $user_roles ) ) { /* if current role not added in $role */
						$roles[ $role ] = 0; /* add new field in array and set role counter == 0 */
						foreach ( $users_roles_list as $value ) {
							/* this check is needed to create $user_roles[ $role ] because function in_array() not work with empty aray */
							if ( empty( $roles[ $role ] ) ) { 
								if ( $role == key( unserialize( $value['meta_value'] ) ) ) { /* if user have current capability */
									$roles[ $role ] ++;
									$user_roles[ $role ][] = $value['user_id']; /* insert in array ID of user to check later if user was already added */
									unset( $users_roles_list[$key] ); /* delete from array records about user data to make sorting more faster */
								}
							} else {
								if ( ! in_array( $value['user_id'], $user_roles[ $role ] ) ) {
									if ( $role == key( unserialize( $value['meta_value'] ) ) ) { /* if user have current capability */
										$roles[ $role ] ++;
										$user_roles[ $role ][] = $value['user_id']; /* insert in array ID of user to check later if user was already added */
										unset( $users_roles_list[$key] ); /* delete from array records about user data to make sorting more faster */
									}
								}
							}
						}
					}
				}
			}
		} else {
			$rol = $wpdb->get_results(
				"SELECT `meta_value`, COUNT(`meta_value`) AS `role_count`, 
					( SELECT COUNT(`id_user`) FROM `" . $wpdb->prefix . "sndr_mail_users_info` LEFT JOIN `" . $wpdb->prefix . "usermeta` ON  `" . $wpdb->prefix . "usermeta`.`user_id`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user` WHERE `meta_key` = '" . $wpdb->prefix . "capabilities' AND `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . ") AS `all` 
				FROM `" . $wpdb->prefix . "usermeta` 
					LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info` ON  `" . $wpdb->prefix . "usermeta`.`user_id`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user`
				WHERE `meta_key` = '" . $wpdb->prefix . "capabilities' AND `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . " GROUP BY `meta_value`",
				ARRAY_A 
			);			
			if ( empty( $rol) ) {
				$all_count = 0;
			} else {
				foreach ( $rol as $r ) {
					$key = array_keys( unserialize( $r['meta_value'] ) );
					if ( empty( $roles ) ) {
						$roles[ $key[0] ] = $r['role_count'];
					} else {
						if ( ! array_key_exists( $key[0], $roles ) ) {
							$roles[ $key[0] ] = $r['role_count'];
						} else {
							$roles[ $key[0] ] += $r['role_count'];
						}
					}
					$all_count = $r['all'];
				}
			}
		} /* deduce the mail form */ 

		if ( is_multisite() ) {
			$link = '<a href="' . network_admin_url( 'admin.php?page=view_mail_send' ) . '">'. __( 'Reports', 'sender' ) . '</a>';
		} else {
			$link = '<a href="' . admin_url( 'admin.php?page=view_mail_send' ) . '">'. __( 'Reports', 'sender' ) . '</a>';
		} ?>

		<div class="sndr-mail wrap" id="sndr-mail">
			<h1><?php echo $title; ?></h1>
			<?php $action_message = sndr_report_actions();
			if ( $action_message['error'] ) {
				$sndr_error = $action_message['error'];
			} elseif ( $action_message['done'] ) {
				$sndr_message = $action_message['done'] . " " . __( 'You can check sending reports on the page', 'sender' ) . " " . $link;
			} ?>
			<div class="error below-h2" <?php if ( empty( $sndr_error ) ) { echo 'style="display:none;"'; } ?>><p><strong><?php echo $sndr_error; ?></strong></div>
			<div class="updated below-h2" <?php if ( empty( $sndr_message ) ) echo 'style="display:none;"'; ?>><p><?php echo $sndr_message; ?></p></div>
			<form method="post">
				<table id="sndr-mail-send-table" class="form-table">
					<tr>
						<th><?php _e( 'Send to', 'sender' ); ?></td>
						<td>
							<label class="sndr-user-roles">
								<input class='sndr-check-all' type="checkbox" name="sndr_send_all" value="1" <?php if ( isset( $_POST['sndr_send_all'] ) && '1' == $_POST['sndr_send_all'] ) { echo 'checked="checked"'; } ?>/> 
								<?php _e( 'all', 'sender' ); ?>	( <span class="sndr-count"><?php echo $all_count; ?></span> )
							</label>
							<?php foreach ( $roles as $role => $value ) {
								if ( isset( $_POST['sndr_user_name'] ) && array_key_exists( $role, $_POST['sndr_user_name'] ) && '1' == $_POST['sndr_user_name'][ $role ]) {
									$checked = 'checked="checked"';
									$user_count_by_roles += intval( $value );
								} else {
									$checked = null;
								} ?>
								<br />
								<label>
									<input class="sndr-role" type="checkbox" name="sndr_user_name[<?php echo $role; ?>]" value="1" <?php echo $checked; ?> />
									<?php echo $role; ?> ( <span class="sndr-count"><?php echo $value; ?></span> )
								</label>
							<?php } ?>
							<br/>
							<span class="bws_info"><?php _e( 'Number of mails which would be sent', 'sender' ); ?>:
								<span id="sndr-calculate">
									<?php if ( isset( $_POST['sndr_send_all'] ) && '1' == $_POST['sndr_send_all'] ) {
										echo $all_count; 
									} elseif ( ! empty( $user_count_by_roles ) ) {
										echo $user_count_by_roles;
									} else {
										echo '0';
									} ?>
								</span> . 
									<?php if ( is_multisite() ) { _e( 'WARNING: as you are using multisite, the total number of sent mails may not coincide with the counter. It will be fixed in the stable version of plugin.', 'sender' ); } ?>
							</span>
						</td>	
					</tr>
					<tr>
						<th><?php _e( 'Subject', 'sender' ); ?></th>
						<td>
							<input id="sndr-mail-subject" type="text" name="sndr_subject" value="<?php if ( isset( $_POST['sndr_subject'] ) && ( ! empty( $_POST['sndr_subject'] ) ) ) { echo stripslashes( esc_html( $_POST['sndr_subject'] ) ); } ?>"/>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Content', 'sender' ); ?></th>
						<td>
							<textarea id="sndr-mail-body" name="sndr_content"><?php if ( isset( $_POST['sndr_content'] ) && ( ! empty( $_POST['sndr_content'] ) ) ) { echo stripslashes( esc_html( $_POST['sndr_content'] ) ); } ?></textarea>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" value="<?php _e( 'Send', 'sender' ); ?>" class="button-primary">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sndr_create_mailout_nonce_name' ); ?>
				</p>
			</form>
		</div><!-- #sndr-mail .sndr-mail -->
	<?php }
}

/**
 * create class SNDR_Report_List for displaying list of mail statistic
 * 
 * check if file exists (for WP before 3.1)
 */
if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ) ) {
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	if ( ! class_exists( 'SNDR_Report_List' ) ) {
		class SNDR_Report_List extends WP_List_Table {

			/**
			* Constructor of class 
			*/
			function __construct() {
				global $status, $page;
				parent::__construct( array(
					'singular'  => __( 'report', 'sender' ),
					'plural'    => __( 'reports', 'sender' ),
					'ajax'      => true,
					)
				);
			}

			/**
			* Function to prepare data before display 
			* @return void
			*/
			function prepare_items() {
				global $wpdb, $sndr_url;

				$paged 				= isset( $_GET['paged'] ) ? '&paged=' . $_GET['paged'] : '';
				$mail_status 		= isset( $_REQUEST['mail_status'] ) ? '&mail_status=' . $_REQUEST['mail_status'] : '';
				$orderby 			= isset( $_REQUEST['orderby'] ) ? '&orderby=' . $_REQUEST['orderby'] : '';
				$order 				= isset( $_REQUEST['order'] ) ? '&order=' . $_REQUEST['order'] : '';

				$sndr_url = '?page=view_mail_send' . $paged . $mail_status . $orderby . $order;

				$columns               = $this->get_columns();
				$hidden                = array();
				$sortable              = $this->get_sortable_columns();
				$this->_column_headers = array( $columns, $hidden, $sortable );
				$this->items           = $this->report_list();
				$per_page              = $this->get_items_per_page( 'reports_per_page', 30 );
				$total_items           = $this->items_count();
				$this->set_pagination_args( array(
						'total_items' => $total_items,
						'per_page'    => $per_page,
					)
				);
			}

			/**
			* Function to show message if no reports found
			* @return void
			*/
			function no_items() { ?>
				<p style="color:red;"><?php _e( 'No messages sent', 'sender' ); ?></p>
			<?php }

			/**
			 * Get a list of columns.
			 * @return array list of columns and titles
			 */
			function get_columns() {
				$columns = array(
					'cb'         => '<input type="checkbox" />',
					'subject'    => __( 'Subject', 'sender' ),
					'status'     => __( 'Status', 'sender' ),
					'date'       => __( 'Date', 'sender' ),
				);
				return $columns;
			}

			/**
			 * Get a list of sortable columns.
			 * @return array list of sortable columns
			 */
			function get_sortable_columns() {
				$sortable_columns = array(
					'subject' => array( 'subject', false ),
					'status'  => array( 'status', false ),
					'date'    => array( 'date', false )
				);
				return $sortable_columns;
			}

			/**
			* Function to add filters below and above mailout list
			* @return array $status_links  
			*/
			function get_views() {
				global $wpdb;
				$status_links  = array();
				$all_count     = $done_count = $progress_count = 0;
				$filters_count = $wpdb->get_results (
					"SELECT COUNT(`mail_send_id`) AS `all`,
						( SELECT COUNT(`mail_send_id`) FROM " . $wpdb->prefix . "sndr_mail_send WHERE `mail_status`=1 ) AS `done`,
						( SELECT COUNT(`mail_send_id`) FROM " . $wpdb->prefix . "sndr_mail_send WHERE `mail_status`=0 ) AS `in_progress`
					FROM " . $wpdb->prefix . "sndr_mail_send"
				); 
				foreach ( $filters_count as $count ) {
					$all_count			= empty( $count->all ) ? 0 : $count->all;
					$done_count			= empty( $count->done ) ? 0 : $count->done;
					$progress_count 	= empty( $count->in_progress ) ? 0 : $count->in_progress;
				}
				
				/* get class for action links */
				$all_class      = ( ! isset( $_REQUEST['mail_status'] ) ) ? ' current': '';
				$progress_class = ( isset( $_REQUEST['mail_status'] ) && "progress_mailout" == $_REQUEST['mail_status'] ) ? ' current': '';
				$done_class     = ( isset( $_REQUEST['mail_status'] ) && "done_mailout" == $_REQUEST['mail_status'] ) ? ' current': '';
				/* get array with action links */
				$status_links['all']   = '<a class="sndr-filter' . $all_class . '" href="?page=view_mail_send">' . __( 'All', 'sender' ) . '<span class="sndr-count"> ( ' . $all_count . ' )</span></a>';
				$status_links['in_progress'] = '<a class="sndr-filter' . $progress_class . '" href="?page=view_mail_send&mail_status=in_progress">' . __( 'In Progress', 'sender' ) . '<span class="sndr-count"> ( ' . $progress_count . ' )</span></a>';
				$status_links['done'] = '<a class="sndr-filter' . $done_class . '" href="?page=view_mail_send&mail_status=done">' . __( 'Done', 'sender' ) . '<span class="sndr-count"> ( ' . $done_count . ' )</span></a>';
				return $status_links;
			}

			/**
			 * Function to add action links to drop down menu before and after reports list
			 * @return array of actions
			 */
			function get_bulk_actions() {
				$actions = array();
				$actions['delete_reports']  = __( 'Delete Reports', 'sender' );
				return $actions;
			}

			/**
			 * Fires when the default column output is displayed for a single row.
			 * @param string $column_name      The custom column's name.
			 * @param int    $item->comment_ID The custom column's unique ID number.
			 * @return void
			 */
			function column_default( $item, $column_name ) {
				switch ( $column_name ) {
					case 'status':
					case 'date':
					case 'subject':
						return $item[ $column_name ];
					default:
						return print_r( $item, true ) ;
				}
			}

			/**
			 * Function to add column of checboxes 
			 * @param int    $item->comment_ID The custom column's unique ID number.
			 * @return string                  with html-structure of <input type=['checkbox']>
			 */
			function column_cb( $item ) {
				return sprintf( '<input id="cb_%1$s" type="checkbox" name="report_id[]" value="%2$s" />', $item['id'], $item['id'] );
			}

			/**
			 * Function to add action links to subject column depenting on status page
			 * @param int      $item->comment_ID The custom column's unique ID number.
			 * @return string                     with action links
			 */
			function column_subject( $item ) {
				global $sndr_url;
				$actions = array();

				$list_per_page = isset( $_REQUEST['list_per_page'] ) ? $_REQUEST['list_per_page'] : 30;

				$actions['show_report'] = '<a class="sndr-show-users-list" href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $item['id'], 'sndr_show_report' . $item['id'] ) . '">' . __( 'Show Report', 'sender' ) . '</a>';
				if ( isset( $_REQUEST['action'] )  && $_REQUEST['action'] == 'show_report' && $_REQUEST['report_id'] == $item['id'] ) {
					unset( $actions['show_report'] );
					$actions['hide_report'] = '<a href="' . wp_nonce_url( $sndr_url . '&action=hide_report&report_id=' . $item['id'], 'sndr_hide_report' . $item['id'] ) . '">' . __( 'Hide Report', 'sender' ) . '</a>';
				}
				$actions['delete_report']  = '<a href="' . wp_nonce_url( $sndr_url . '&action=delete_report&report_id=' . $item['id'], 'sndr_delete_report' . $item['id'] ) . '">' . __( 'Delete Report', 'sender' ) . '</a>';
				return sprintf( '%1$s %2$s', $item['subject'], $this->row_actions( $actions ) );
			}

			/**
			 * Display status of mailout
			 * @param    array    $item             current mailout data
			 * @return   string   $column_content   with action links
			 */
			function column_status( $item ) {
				global $wpdb;

				$report = $item['id'];

				/* check if table has PRO-column and adjust our query to exclude receivers from PRO mailous */
				$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_users` LIKE 'id_mailout';" );
				$additional_condition = ( 0 == $colum_exists ) ? "" : " AND ( `id_mailout`=`id_mail` OR `id_mailout`=0 )";
				
				$all_result = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $report . $additional_condition . " ;" );

				$done = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $report . $additional_condition . " AND `status`=1;" );

				switch ( $item['status'] ) {
					case '0': /* mailout in progress */
						$column_content = '<span class="sndr-inprogress-label">' . __( 'In Progress', 'sender' ) . ' ( ' . $done . ' ' . __( 'of', 'sender') . ' ' . $all_result . ' ) ' . '</span>';
						break;
					case '1': /* mailout was done */
						$column_content = '<span class="sndr-done-label">' . __( 'All Done', 'sender' ) . '</span>';
						break;
					default:
						$column_content = '';
						break;
				}
				return $column_content;
			}

			/**
			 * Function to add necessary class and id to table row
			 * @param array $report with report data 
			 * @return void
			 */
			function single_row( $report ) {
				if ( preg_match( '/done-status/', $report['status'] ) ) {
					$row_class = 'sndr-done-row';
				} elseif( preg_match( '/inprogress-status/', $report['status'] ) ) {
					$row_class = 'sndr-inprogress-row';
				} else {
					$row_class = null;
				}
				echo '<tr id="report-' . $report['id'] . '" class="' . trim( $row_class ) . '">';
					$this->single_row_columns( $report );
				echo "</tr>\n";
			}
			
			/**
			 * Function to get report list
			 * @return array list of reports
			 */
			function report_list() {
				global $wpdb;
				$i                  = 0;
				$reports_list       = array();  
				$per_page = intval( get_user_option( 'reports_per_page' ) );
				if ( empty( $per_page ) || $per_page < 1 ) {
					$per_page = 30;
				}

				$start_row = ( isset( $_REQUEST['paged'] ) && '1' != $_REQUEST['paged'] ) ? $per_page * ( absint( $_REQUEST['paged'] ) - 1 ) : 0;
				
				if ( isset( $_REQUEST['orderby'] ) ) {
					switch ( $_REQUEST['orderby'] ) {
						case 'date':
							$order_by = 'date_create';
							break;
						case 'subject':
							$order_by = 'subject';
							break;
						case 'status':
							$order_by = 'mail_status';
							break;
						default:
							$order_by = 'mail_send_id';
							break;
					}
				} else {
					$order_by = 'mail_send_id';
				}
				$order     = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'DESC';
				$sql_query = "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send`";
				if ( isset( $_REQUEST['s'] ) ) {
					$sql_query .= "WHERE `subject`LIKE '%" . $_REQUEST['s'] . "%'";
				} else {
					if ( isset( $_REQUEST['mail_status'] ) ) {
						switch ( $_REQUEST['mail_status'] ) {
							case 'in_progress':
								$sql_query .= "WHERE `mail_status`=0";
								break;
							case 'done':
								$sql_query .= "WHERE `mail_status`=1";
								break;
							default:
								break;
						}
					}
				}
				$sql_query   .= " ORDER BY " . $order_by . " " . $order . " LIMIT " . $per_page . " OFFSET " . $start_row . ";";
				$reports_data = $wpdb->get_results( $sql_query, ARRAY_A );
				foreach ( $reports_data as $report ) {
					$subject = empty( $report['subject'] ) ? '( ' . __( 'No Subject', 'sender' ) . ' )' : $report['subject'];
					$reports_list[$i]            = array();
					$reports_list[$i]['id']      = $report['mail_send_id'];
					$reports_list[$i]['status']  = $report['mail_status'];
					$reports_list[$i]['subject'] = $subject . '<input type="hidden" name="report_' . $report['mail_send_id'] . '" value="' . $report['mail_send_id'] . '">' . $this->show_report( $report['mail_send_id'] );
					$reports_list[$i]['date']    = date( 'd M Y H:i', $report['date_create'] );
					$i ++;
				}
				return $reports_list;
			}

			/**
			 * Function to get number of all reports
			 * @return sting reports number
			 */
			public function items_count() {
				global $wpdb;
				$sql_query = "SELECT COUNT(`mail_send_id`) FROM `" . $wpdb->prefix . "sndr_mail_send`";
				if ( isset( $_REQUEST['mail_status'] ) ) {
					switch ( $_REQUEST['mail_status'] ) {
						case 'in_progress':
							$sql_query .= " WHERE `mail_status`=0;";
							break;
						case 'done':
							$sql_query .= " WHERE `mail_status`=1;";
							break;
						default:
							break;
					}
				}
				$items_count  = $wpdb->get_var( $sql_query );
				return $items_count;
			}

			/**
			 * Function to show list of subscribers
			 * @param string $mail_id id of report 
			 * @return string         list of subscribers in table format
			 */
			public function show_report( $mail_id ) {
				$list_table = null;
				if ( isset( $_REQUEST['action'] ) && 'show_report' == $_REQUEST['action'] && $mail_id == $_REQUEST['report_id'] && check_admin_referer( 'sndr_show_report' . $mail_id ) ) {
					global $wpdb, $sndr_url;

					$report    		= intval( $_REQUEST['report_id'] );
					$list_paged 	= isset( $_GET['list_paged'] ) ? intval( $_GET['list_paged'] ) : 1;
					$list_order_by 	= isset( $_REQUEST['list_order_by'] ) ? $_REQUEST['list_order_by'] : 'user_display_name';
					$list_per_page 	= isset( $_REQUEST['list_per_page'] ) ? $_REQUEST['list_per_page'] : 30;
					
					if ( isset( $_REQUEST['list_order'] ) ) {
						$list_order = ( 'ASC' == $_REQUEST['list_order'] ) ? 'DESC' : 'ASC';
						$link_list_order = $_REQUEST['list_order'];
					} else {
						$list_order = $link_list_order = 'ASC';
					}

					$start_row   = ( 1 < $list_paged ) ? $list_per_page * ( $list_paged - 1 ) : 0;
					/* check if table has PRO-column and adjust our query to exclude receivers from PRO mailous */
					$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_users` LIKE 'id_mailout';" );
					$additional_condition = ( 0 == $colum_exists ) ? "" : " AND ( `id_mailout`=`id_mail` OR `id_mailout`=0 )";
					
					$users_list  = $wpdb->get_results( 
						"SELECT DISTINCT `" . $wpdb->prefix . "sndr_users`.`id_user`,`status`, `view`, `try`, `user_display_name`, `user_email` 
						FROM `" . $wpdb->prefix . "sndr_users` 
						LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info`  ON `" . $wpdb->prefix . "sndr_users`.`id_user`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user`
						WHERE `id_mail`=" . $report . $additional_condition . " ORDER BY " . $list_order_by . " " . $list_order . " LIMIT " . $list_per_page . " OFFSET " . $start_row . ";"
					);
					
					if ( ! empty( $users_list ) ) { 
						$list_table =
							'<table class="report">
								<thead>
									<tr scope="row">
										<td colspan="4">' . $this->subscribers_pagination( $report, $list_per_page, $list_paged, $list_order_by, $link_list_order, 'top' ) . '</td>
									</tr>
									<tr>
										<td class="sndr-username"><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=user_display_name&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Username', 'sender' ) . '</a></td>
										<td><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=status&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Status', 'sender' ) . '</a></td>
										<td>' . __( 'Try', 'sender' ) . '</td>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td class="sndr-username"><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=user_display_name&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Username', 'sender' ) . '</a></td>
										<td><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=status&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Status', 'sender' ) . '</a></td>
										<td>' . __( 'Try', 'sender' ) . '</td>
									</tr>
									<tr scope="row">
										<td colspan="4">' . $this->subscribers_pagination( $report, $list_per_page, $list_paged, $list_order_by, $link_list_order, 'bottom' ) . '</td>
									</tr>
								</tfoot>
								<tbody>';
						foreach ( $users_list as $list ) {
							$user_name = empty( $list->user_display_name ) ? $list->user_email : $list->user_display_name;
							if ( empty( $user_name ) ) {
								$user_name = '<i>- ' . __( 'User was deleted', 'sender' ) . ' -</i>';
							}
							$list_table .= '<tr>
												<td class="sndr-username">' . $user_name . '</td>
												<td>';
							if ( '1' == $list->status ) {
								$list_table .= '<p style="color: green;">' . __( 'received', 'sender' ) . '</p>';
							} else { 
								$list_table .= '<p style="color: #555;">' . __( 'in the queue', 'sender' ) . '</p>'; 
							}
							$list_table .=		'</td>
												<td style="display: none;">';
							if ( '1' == $list->view ) {
								$list_table .= '<p style="color: green;">' . __( 'read', 'sender' ) . '</p>';
							} else { 
								$list_table .= '<p style="color: #555;">' . __( 'not read', 'sender' ) . '</p>'; 
							}
							$list_table .=	'</td>
											<td>' . $list->try . '</td>
										</tr>';
						}
						$list_table .= 
								'</tbody>
							</table>
						<input type="hidden" name="sndr_url" value="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $report . '&list_order_by=status&list_order=' . $list_order, 'sndr_show_report' . $report ) . '" />';
					} else {
						$list_table = '<p style="color:red;">' . __( "The list of subscribers can't be found.", 'sender' ) . '</p>';
					}
				}
				return $list_table;
			}

			/** 
			 * Function to get subscribers list pagination
			 * @param string  $mail_id        id of report
			 * @param string  $list_per_page       number of subscribers on each page
			 * @param string  $list_paged          desired page number
			 * @param string  $list_order_by  on what grounds will be sorting
			 * @param string  $list_order     "ASC" or "DESC
			 * @param string  $place          postfix to fields name
			 * @return string                 pagination elements
			 */
			function subscribers_pagination( $mail_id, $list_per_page, $list_paged, $list_order_by, $list_order, $place ) {
				global $wpdb, $sndr_url;
				$users_count = $wpdb->get_var(
					"SELECT COUNT( `id_user` ) FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $mail_id . ";"
				);

				/* open block with pagination elements */
				$pagination_block = 
					'<div class="sndr-pagination">
						<p class="total-users">' . __( 'Total Subscribers:', 'sender' ) . ' ' . $users_count . '</p>
						<div class="list-per-page">
							<input type="number" min="1" max="1000" class="sndr_set_list_per_page small-text hide-if-no-js" name="set_list_per_page_' . $place . '" value="' . $list_per_page . '" title="' . __( 'Number of Subscribers on Page', 'sender' ) . '" />
							<span class="total_pages"><span class="hide-if-js">' . $list_per_page . '</span>' . __( 'on page', 'sender' ) . '</span>
						</div>';

				/* if more than 1 page */
				if ( intval( $users_count ) > $list_per_page ) {
					/* get number of all pages */
					$total_pages         = ceil( $users_count / $list_per_page );
					
					$pagination_block .= 
						'<div class="list-paged">';
					if ( 1 < $list_paged ) { /* if this is NOT first page of subscribers list */
						$previous_page_link = $list_paged - 1;
						$pagination_block .= 
							'<a class="first-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=1&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the First Page', 'sender' ) . '">&laquo;</a>
							<a class="previous-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=' . $previous_page_link . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the Previous Page', 'sender' ) . '">&lsaquo;</a>';
					} else { /* if this is first page of subscribers list */
						$pagination_block .= 
							'<span class="first-page-disabled">&laquo;</span>
							<span class="previous-page-disabled">&lsaquo;</span>';
					}
					/* field to choose number of subscribers on page and current page */
					$pagination_block .= 
						'<input type="number" class="sndr_list_paged hide-if-no-js small-text" min="1" max="' . $total_pages . '" name="list_paged_' . $place . '" value="' . $list_paged . '" title="' . __( 'Current Page', 'sender' ) . '"/>
						<span class="total_pages"><span class="hide-if-js">' . $list_paged . '</span>' . __( 'of', 'sender' ) . ' ' . $total_pages . ' ' . __( 'pages', 'sender' ) . '</span>';

					if ( $list_paged < $total_pages ) { /* if this is NOT last page */
						$next_page_link = $list_paged + 1;
						$pagination_block .= 
							'<a class="next-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=' . $next_page_link . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the Next Page', 'sender' ) . '">&rsaquo;</a>
							<a class="last-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=' . $total_pages . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the Last Page', 'sender' ) . '">&raquo;</a>';
					} else { /* if this is last page */
						$pagination_block .= 
							'<span class="next-page-disabled">&rsaquo;</span>
							<span class="last-page-disabled">&raquo;</span>';
					}
					$pagination_block .= '</div><!-- .list-paged -->';
				}
				/* close block with pagination elememnts */
				$pagination_block .= '</div><!-- .sndr-pagination -->';
				return $pagination_block;
			}
		}
	}
}
/* the end of the SNDR_Report_List class definition */

/**
 * Add screen options and initialize instance of class SNDR_Report_List
 * @return void 
 */
if ( ! function_exists( 'sndr_screen_options' ) ) {
	function sndr_screen_options() {
		global $sndr_reports_list;

		sndr_add_tabs();

		$args = array(
			'label'   => __( 'Reports per page', 'sender' ),
			'default' => 30,
			'option'  => 'reports_per_page'
		);
		add_screen_option( 'per_page', $args );
		$sndr_reports_list = new SNDR_Report_List();
	}
	
}

/** 
 * Add help tab on settings page
 * @return void 
 */
if ( ! function_exists( 'sndr_add_tabs' ) ) {
	function sndr_add_tabs() {
		$args = array(
			'id'      => 'sndr',
			'section' => '200538639'
		);
		bws_help_tab( get_current_screen(), $args );
	}
}

/**
 * Function to save and load settings from screen options
 * @return void 
 */
if ( ! function_exists( 'sndr_table_set_option' ) ) {
	function sndr_table_set_option( $status, $option, $value ) {
		return $value;
	}
}

/**
 * Function to display template of reports page
 * @return void 
 */
if ( ! function_exists( 'sndr_mail_view' ) ) {
	function sndr_mail_view() { 
		global $sndr_message, $sndr_error, $sndr_reports_list; ?>
		<div class="wrap sndr-report-list-page">
			<h1><?php _e( 'Sender Reports', 'sender' ); ?></h1>
			<?php $action_message = sndr_report_actions();
			if ( $action_message['error'] ) {
				$sndr_error = $action_message['error'];
			} elseif ( $action_message['done'] ) {
				$sndr_message = $action_message['done'];
			} ?>
			<div class="error below-h2" <?php if ( empty( $sndr_error ) ) { echo 'style="display:none"'; } ?>><p><strong><?php echo $sndr_error; ?></strong></div>
			<div class="updated below-h2" <?php if ( empty( $sndr_message ) ) echo 'style="display: none;"'?>><p><?php echo $sndr_message ?></p></div>
			<?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) {
				printf( '<span class="subtitle">' . sprintf( __( 'Search results for &#8220;%s&#8221;', 'sender' ), wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) ) . '</span>' );
			} 
			echo '<h2 class="screen-reader-text">' . __( 'Filter reports list', 'sender' ) . '</h2>';
			$sndr_reports_list->views(); ?>
			<form method="post">
				<?php $sndr_reports_list->prepare_items();
				$sndr_reports_list->search_box( __( 'search', 'sender' ), 'sndr' );
				$sndr_reports_list->current_action();
				$sndr_reports_list->display(); 
				wp_nonce_field( plugin_basename( __FILE__ ), 'sndr_mail_view_nonce_name' ); ?>
			</form>
		</div><!-- .wrap .sndr-report-list-page -->
	<?php }
}

/**
 * Function to handle actions from "report" and "edit mail" page 
 * @return array with messages about action results
 */
if ( ! function_exists( 'sndr_report_actions' ) ) {
	function sndr_report_actions() {
		global $wpdb;
		$blogusers_id   = $blogusers = array();
		$action_message = array(
			'error' => false,
			'done'  => false
		);
		$error = $done = $mail_error = $mail_done = 0;
		if ( isset( $_REQUEST['page'] ) && ( 'view_mail_send' == $_REQUEST['page'] || 'sndr_send_user' == $_REQUEST['page'] ) ) {
			$message_list = array(
				'empty_reports_list'    => __( 'You need to choose some reports.', 'sender' ),
				'report_delete_error'   => __( 'Error while deleting report.', 'sender' ),
				'mail_delete_error'     => __( 'Error while deleting mail.', 'sender' ),
				'empty_content'         => __( 'You cannot send an empty mail.', 'sender' ),
				'empty_users_list'      => __( 'Select a list of users to send messages.', 'sender' ),
				'cannot_get_users_list' => __( 'It is impossible to get the list of users.', 'sender' ),
				'try_later'             => __( 'Please, try it later.', 'sender' ),
				'new_mailout_create'    => __( 'New mailout was created successfully.', 'sender' ),
				'mailout_not_create'    => __( 'Mailout was not create', 'sender' )
			);
			if ( isset( $_REQUEST['action'] ) || isset( $_REQUEST['action2'] ) ) {
				$action = '';
				if ( isset( $_REQUEST['action'] ) && '-1' != $_REQUEST['action'] ) {
					$action = $_REQUEST['action'];
				} elseif ( isset( $_POST['action2'] ) && '-1' != $_REQUEST['action2'] ) {
					$action = $_POST['action2'];
				}
				switch ( $action ) {
					case 'delete_report':
						if ( check_admin_referer( 'sndr_delete_report' . $_GET['report_id'] ) ) {
							if ( empty( $_GET['report_id'] ) ) {
								$action_message['error'] = $message_list['empty_reports_list'];
							} else {
								$report = $_GET['report_id'];
								/* delete all records about mail statistics */
								$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $report );
								if ( $wpdb->last_error ) {
									$error ++;
								} else {
									$done ++;
								}
								/* delete mail */
								$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $report );
								if ( $wpdb->last_error ) { 
									$mail_error ++;
								} else {
									$mail_done ++;
								}
								/* set message */
								if ( 0 == $error && 0 == $mail_error ) {
									$action_message['done'] = sprintf( _nx( __( 'Report was deleted.', 'sender'), '%s&nbsp;' . __( 'Reports were deleted.', 'sender'), $done, 'sender' ), number_format_i18n( $done ) );
								} else {
									if ( 0 != $error ) {
										$action_message['error'] = $message_list['report_delete_error'] . '<br/>' . $message_list['try_later'];
									} elseif ( 0 != $mail_error ) {
										$action_message['error'] = $message_list['mail_delete_error'] . '<br/>' . $message_list['try_later'];
									}
								}
							}
						}
						break;
					case 'delete_reports':
						if ( check_admin_referer( plugin_basename( __FILE__ ), 'sndr_mail_view_nonce_name' ) ) {
							if ( empty( $_POST['report_id'] ) ) {
								$action_message['error'] = $message_list['empty_reports_list'];
							} else {
								foreach ( $_POST['report_id'] as $report ) {
									/* delete all records about mail statistics */
									$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $report );
									if ( $wpdb->last_error ) {
										$error ++;
									} else {
										$done ++;
									}
									/* delete mail */
									$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $report );
									if ( $wpdb->last_error ) {
										$mail_error ++;
									} else {
										$mail_done ++;
									}
								}
								/* set message */
								if ( 0 == $error && 0 == $mail_error ) {
									$action_message['done'] = sprintf( _nx( __( 'Report was deleted.', 'sender'), '%s&nbsp;' . __( 'Reports were deleted.', 'sender'), $done, 'sender' ), number_format_i18n( $done ) );
								} else {
									if ( 0 != $error ) {
										$action_message['error'] = $message_list['report_delete_error'] . '<br/>' . $message_list['try_later'];
									} elseif ( 0 != $mail_error ) {
										$action_message['error'] = $message_list['mail_delete_error'] . '<br/>' . $message_list['try_later'];
									}
								}
							}
						}
						break;
					case 'show_report':
					default:
						break;
				}
			/* add messages to database and registered cron */
			} elseif ( isset( $_POST['sndr_subject'] ) && isset( $_POST['sndr_content'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sndr_create_mailout_nonce_name' ) ) {
				if ( empty( $_POST['sndr_content'] ) ) { /* if empty content of mail */
					$action_message['error'] = $message_list['empty_content'];
				} elseif ( isset( $_POST['sndr_send_all'] ) || isset( $_POST['sndr_user_name'] ) ) { /* if not empty users list */
					$add_condition = ( function_exists( 'sbscrbr_users_list' ) || function_exists( 'sbscrbrpr_users_list' ) ) ? " AND `black_list`=0 AND `delete`=0" : '';
					$blogusers_id = array();
					/* Save mail into database */
					$mail_data = array(
						'subject'		=> stripslashes( esc_html( $_POST['sndr_subject'] ) ),
						'body'			=> $_POST['sndr_content'],
						'date_create'	=> time() 
					);
					if ( function_exists( 'mlq_if_mail_plugin_is_in_queue' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
						$mail_data['remote_delivery']	= '1';
					}
					$wpdb->insert( 
						$wpdb->prefix . 'sndr_mail_send', 
						$mail_data
					);
					$last_id       = $wpdb->insert_id;
					if ( isset( $_POST['sndr_send_all'] ) ) { /* get all users */
						$sql_query = "SELECT id_user FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `subscribe`=1" . $add_condition . ";";
					} elseif ( isset( $_POST['sndr_user_name'] ) ) { /* get users by selected role */
						$sql_query    = '';
						$i            = 0;
						$last_element = count( $_POST['sndr_user_name'] );
						foreach ( $_POST['sndr_user_name'] as $key=>$value ) {
							$sql_query .= 
								"SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta`
									LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info` ON " . $wpdb->prefix . "sndr_mail_users_info.id_user=" . $wpdb->prefix . "usermeta.user_id
								WHERE `meta_value` LIKE '%\"" . $key . "\"%' AND `subscribe`=1" . $add_condition;
							$i ++;
							if ( $last_element !== $i ) { /* if this is not last element of array */
								$sql_query .= " UNION ";
							} else {
								$sql_query .= ";";
							}
						}
					}

					if ( ! empty( $sql_query ) ) {
						$users = $wpdb->get_results( $sql_query, ARRAY_A );
						$array_key = isset( $_POST['sndr_send_all'] ) ? 'id_user' : 'user_id';
						foreach ( $users as $key => $value ) {
							if ( empty( $blogusers_id ) ) {
								$blogusers_id[] = $value[$array_key];
							} else {
								if ( ! in_array( $value[$array_key], $blogusers_id ) ) {
									$blogusers_id[] = $value[$array_key];
								}
							}
						}
					}
					if ( ! empty( $blogusers_id ) ) {
						do_action( 'sndr_get_mail_data', plugin_basename( __FILE__ ), $blogusers_id, stripslashes( esc_html( $_POST['sndr_subject'] ) ), $_POST['sndr_content'], $last_id );

						foreach ( $blogusers_id as $bloguser ) {
							$wpdb->insert( 
								$wpdb->prefix . 'sndr_users',
								array( 
									'id_user' => $bloguser,
									'id_mail' => $last_id,
									'status'  => 0,
									'view'    => 0 
								)
							);
						}
						/*Activation cron hook*/
						add_filter( 'cron_schedules', 'sndr_more_reccurences' );
						if ( ! wp_next_scheduled( 'sndr_mail_hook' ) ) {
							$check = wp_schedule_event( time(), 'my_period', 'sndr_mail_hook' );
							if ( empty( $check ) ) {
								$action_message['done'] = $message_list['new_mailout_create'];
							} elseif ( ! $check ) {
								$action_message['error'] = $message_list['mailout_not_create'];
							}
						} else {
							$action_message['done'] = $message_list['new_mailout_create'];
						}
					} else {
						$action_message['error'] = $message_list['cannot_get_users_list'];
					}
				} else { /* if empty users list */
					$action_message['error'] = $message_list['empty_users_list'];
				}
			}
		}
		return $action_message;
	}
}

/**
 * Function to change mail status to "sent" if the letter was sent via Email Queue plugin.
 * @param arr $mail 		current mailout info ( user_id and mail_id)
 * @param int $mailing_try	mailing try for current mailout
 * @param bool $sent_or_not	whether message was successfuly sent or not
 * @return void 
 */
if ( ! function_exists( 'sndr_get_update_on_mail_from_email_queue' ) ) {
	function sndr_get_update_on_mail_from_email_queue( $mail, $mailing_try, $sent_or_not ) {
		global $wpdb;
		$change_status = $sent_or_not ? "`status`=1, " : "";
		$wpdb->query( "UPDATE `" . $wpdb->prefix . "sndr_users` SET " . $change_status . "`try`=" . $mailing_try . " WHERE `id_mail`= " . $mail['mail_id_in_sender'] . " AND `id_user`=" . $mail['user_id_in_sender'] . ";" );
		if ( $sent_or_not ) {
			/* set sent status on current mailout if no unsent users left  */
			$mails = $wpdb->get_var( "SELECT `mail_users_id` FROM `" . $wpdb->prefix . "sndr_users` WHERE `status`='0' AND `id_mail`=" . $mail['mail_id_in_sender'] . ";");
			if ( empty( $mails ) ) {
				/* set done status for current mailout */
				$wpdb->query( "UPDATE `" . $wpdb->prefix . "sndr_mail_send` SET `mail_status`=1 WHERE `mail_send_id`=" . $mail['mail_id_in_sender'] . ";" );
			}
		}
	}
}

/**
 * Function register of users.
 * @param int $user_id user ID
 * @return void 
 */	
if ( ! function_exists( 'sndr_mail_register_user' ) ) {
	function sndr_mail_register_user( $user_id ) {
		global $wpdb;
		/*insert into database register user*/
		$user = get_userdata( $user_id );
		$user_subscribed = $wpdb->query( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `user_email` ='" . $user->user_email . "';" );
		if ( empty( $user_subscribed ) ) {
			$wpdb->insert( $wpdb->prefix . 'sndr_mail_users_info',
				array( 
					'id_user'			=> $user->ID, 
					'user_email'		=> $user->user_email,
					'user_display_name' => $user->display_name,
					'subscribe' 		=> 1 
				)
			);
		} else {
			$wpdb->update( $wpdb->prefix . 'sndr_mail_users_info',
				array( 
					'id_user'			=> $user->ID,
					'user_display_name' => $user->display_name,
					'subscribe' 		=> 1 
				),
				array(
					'user_email'		=> $user->user_email
				)
			);
		}
	}
}

/**
 * Function to show "subscribe" checkbox for users.
 * @param array $user user data
 * @return void
 */
if ( ! function_exists( 'sndr_mail_send' ) ) {
	function sndr_mail_send( $user ) {
		global $wpdb, $current_user;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		/* deduce form the subscribe */
		$current_user = wp_get_current_user();
		if ( function_exists( 'sbscrbr_users_list' ) || function_exists( 'sbscrbrpr_users_list' ) ) { /* if Subscriber plugin already installed and activated */
			$mail_message = $wpdb->get_row( "SELECT `subscribe`, `black_list` FROM `" . $prefix . "sndr_mail_users_info` WHERE `id_user` = '" . $current_user->ID . "' LIMIT 1;", ARRAY_A );
			$disabled     = ( 1 == $mail_message['black_list'] ) ? 'disabled="disabled"' : "";
		} else {
			$mail_message = $wpdb->get_row( "SELECT `subscribe` FROM `" . $prefix . "sndr_mail_users_info` WHERE `id_user` = '" . $current_user->ID . "' LIMIT 1;", ARRAY_A );
			$disabled     = '';
		}
		$confirm = ( ( 1 == $mail_message['subscribe'] ) && ( empty( $disabled ) ) ) ? 'checked="checked"' : ""; ?>
		<table class="form-table" id="mail_user">
			<tr>
				<th><?php _e( 'Subscribe on newsletters', 'sender' ); ?> </th>
				<td>
					<input type="checkbox" name="sndr_mail_subscribe" <?php echo $confirm; ?> <?php echo $disabled; ?> value="1"/>
					<?php if ( ! empty( $disabled ) ) {
						echo '<span class="description">' . __( 'Sorry, but you denied to subscribe to the newsletter.', 'sender' ) . '</span>';
					} ?>
				</td>
			</tr>
		</table>
		<?php 
	}
}

/**
 * Function update user data.
 * @param $user_id         integer
 * @param $old_user_data   array()
 * @return void
 */
if ( ! function_exists( 'sndr_update' ) ) {
	function sndr_update( $user_id, $old_user_data ) {
		global $wpdb, $current_user;
		if ( ! function_exists( 'get_userdata' ) ) {
			require_once( ABSPATH . "wp-includes/pluggable.php" ); 
		}
		/* user who is modified */
		$current_modified_user = get_userdata( $user_id );
		/* change 'subscribe' status if current user is trying to do it on user-edit or profile page */
		$user_exists  = $wpdb->query( "SELECT `id_user` FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `id_user`=" . $current_modified_user->ID . ";" );
		if ( $user_exists ) {
			if ( in_array( $_SERVER['PHP_SELF'], array( '/wp-admin/profile.php', '/wp-admin/network/profile.php' ) ) ) {
				$subscriber   = ( isset( $_POST['sndr_mail_subscribe'] ) && '1' == $_POST['sndr_mail_subscribe'] ) ? '1' : '0';
				$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_mail_users_info` LIKE 'unsubscribe_reason'" );
				if ( ! empty( $colum_exists ) ) {
					$subscriber_reason = ( '0' == $subscriber ) ? __( 'unsubscribed from the user profile', 'sender' ) : NULL;
					$wpdb->update( $wpdb->prefix . 'sndr_mail_users_info',
						array(
							'user_email'        	=> $current_modified_user->user_email,
							'user_display_name' 	=> $current_modified_user->display_name,
							'subscribe'         	=> $subscriber,
							'unsubscribe_reason'	=> $subscriber_reason
						),
						array( 'id_user' => $current_modified_user->ID )
					);
				} else {					
					$wpdb->update( $wpdb->prefix . 'sndr_mail_users_info',
						array(
							'user_email'        => $current_modified_user->user_email,
							'user_display_name' => $current_modified_user->display_name,
							'subscribe'         => $subscriber,
						),
						array( 'id_user' => $current_modified_user->ID )
					);
				}
			} else {
				$wpdb->update( $wpdb->prefix . 'sndr_mail_users_info',
					array(
						'user_email'        => $current_modified_user->user_email,
						'user_display_name' => $current_modified_user->display_name,
					),
					array(
						'id_user'           => $current_modified_user->ID
					)
				);				
			}
		} else {
			if ( isset( $_POST['sndr_mail_subscribe'] ) && '1' == $_POST['sndr_mail_subscribe'] ) {
				$wpdb->insert( $wpdb->prefix . 'sndr_mail_users_info',
					array(
						'id_user'           => $current_modified_user->ID,
						'user_email'        => $current_modified_user->user_email,
						'user_display_name' => $current_modified_user->display_name,
						'subscribe'         => 1
					)
				);
			}
		}
		
	}	
}

/**
 * Function to add new preiod between mail sending
 * @return void
 */
if ( ! function_exists( 'sndr_more_reccurences' ) ) {
	function sndr_more_reccurences( $schedules ) {
		$sndr_options = ( is_multisite() ) ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );
		if ( ! empty( $sndr_options ) ) {
			$schedules['my_period'] = array( 'interval' => $sndr_options['sndr_run_time'] * 60, 'display' => __( 'Your interval', 'sender' ) );
			return $schedules;
		}
	}
}

/**
 * Function to periodicaly mail sending
 * @return void
 */
if ( ! function_exists( 'sndr_cron_mail' ) ) {
	function sndr_cron_mail() {
		global $wpdb, $sndr_options;
		$sndr_options = ( is_multisite() ) ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once( ABSPATH . "wp-includes/pluggable.php" );
		}
		if ( 'wp_mail' != $sndr_options['sndr_method'] ) {
			require_once( ABSPATH . WPINC . '/class-phpmailer.php' );
			$mail = new PHPMailer();
		}
		$sended		=	$errors	=	array();
		$from_name	= ( empty( $sndr_options['sndr_from_custom_name'] ) ) ? get_bloginfo( 'name' ) : $sndr_options['sndr_from_custom_name'];
		if ( empty( $sndr_options['sndr_from_email'] ) ) {
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}
			$from_email = 'wordpress@' . $sitename;
		} else
			$from_email	= $sndr_options['sndr_from_email'];

		$from_name	=	'=?UTF-8?B?' . base64_encode( $from_name ) . '?=';

		/* get messages */
		if ( function_exists( 'mlq_if_mail_plugin_is_in_queue' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
			$users_mail_sends = $wpdb->get_results( "
				SELECT * FROM `" . $wpdb->prefix . "sndr_users` AS users 
				JOIN `" . $wpdb->prefix . "sndr_mail_send` AS mails ON (
					users.`id_mail` = mails.`mail_send_id` AND 
					mails.`remote_delivery` = 0 ) 
				WHERE users.`status` = '0' LIMIT " . $sndr_options['sndr_send_count'] . ";", ARRAY_A );
		} else {
			$users_mail_sends = $wpdb->get_results( "SELECT * FROM `" . $wpdb->prefix . "sndr_users` WHERE `status` = '0' LIMIT " . $sndr_options['sndr_send_count'] . ";", ARRAY_A );
		}

		if ( ! empty( $users_mail_sends ) ) {
			foreach ( $users_mail_sends as $users_mail_send ) {
				/* get users */
				$current_message		=	$users_mail_send['id_mail'];
				$mail_message			=	$wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id` = '" . $current_message . "' LIMIT 1;", ARRAY_A );
				$subject				=	'=?UTF-8?B?' . base64_encode( $mail_message['subject'] ) . '?=';
				$user_info				=	$wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `id_user` = '" . $users_mail_send['id_user'] . "' LIMIT 1;", ARRAY_A );
				$mail_message['body']	=	apply_filters( 'sbscrbr_add_unsubscribe_link', $mail_message['body'], $user_info );
				$mail_message['body']	=	apply_filters( 'sbscrbrpr_add_unsubscribe_link', $mail_message['body'], $user_info );
				if ( ! empty( $user_info ) && 1 == $user_info['subscribe'] ) {
					if ( 'wp_mail' != $sndr_options['sndr_method'] ) {
						$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html><head><title></title></head><body>' . stripcslashes( $mail_message['body'] ) . '</body></html>';
					} else {
						$body = stripcslashes( $mail_message['body'] );
					}
					
					/*send message*/
					if ( 'mail' == $sndr_options['sndr_method'] ) {
						$mail->CharSet = 'utf-8';
						$mail->Subject = $subject;
						$mail->MsgHTML( $body );
						$mail->SetFrom( $from_email, $from_name );
						$mail->AddAddress( $user_info['user_email'], $user_info['user_display_name'] );
						$mail->isHTML( true );

						if ( $mail->Send() )
							$sended[] = $users_mail_send;
						else
							$errors[] = $users_mail_send;
						
						$mail->ClearAddresses();
						$mail->ClearAllRecipients();
						
					} else {
						$headers = 'From: ' . $from_name . ' <' . $from_email . '>' . "\r\n";
						if ( wp_mail( $user_info['user_email'], $subject, $body, $headers ) )
							$sended[] = $users_mail_send;
						else
							$errors[] = $users_mail_send;
					}
				}
			}

			/* update users */
			if ( ! empty( $sended ) ) {
				foreach( $sended as $send ) {
					$er = $send['try'] + 1;
					$wpdb->query( "UPDATE `" . $wpdb->prefix . "sndr_users` SET `status`=1, `try`=" . $er . " WHERE `mail_users_id`=" . $send['mail_users_id'] . ";" );
				}
				$mails = $wpdb->get_var( "SELECT `mail_users_id` FROM `" . $wpdb->prefix . "sndr_users` WHERE `status`='0' AND `id_mail`=" . $users_mail_send['id_mail'] . ";");
				if ( empty( $mails ) ) {
					/* set done status for curremt mailout */
					$wpdb->query( "UPDATE `" . $wpdb->prefix . "sndr_mail_send` SET `mail_status`=1 WHERE `mail_send_id`=" . $users_mail_send['id_mail'] . ";" );
					$next_mails = $wpdb->get_var( "SELECT `mail_send_id` FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_status`='0';" );
					/* if not exists another mailouts */
					if ( empty( $next_mails ) ) {
						wp_clear_scheduled_hook( 'sndr_mail_hook' );
					}
				}
			}
			
			if ( ! empty( $errors ) ) {
				foreach( $errors as $error ) {
					$er = $error['try'] + 1;
					$wpdb->query( "UPDATE `" . $wpdb->prefix . "sndr_users` SET `try`=" . $er . " WHERE `mail_users_id`=" . $error['mail_users_id'] . ";" );
				}
			}
		} else {
			wp_clear_scheduled_hook( 'sndr_mail_hook' );
		}
	}
}

/* pro pages */
if ( ! function_exists( 'sndr_create_mailout' ) ) {
	function sndr_create_mailout() {
		global $sndr_plugin_info, $wp_version; ?>
		<div class="bws_pro_version_bloc">
			<div class="bws_pro_version_table_bloc">
				<div class="bws_table_bg" style="top:0;"></div>
				<div class="wrap sndrpr-mail" id="sndrpr-mail" style="margin: 0 10px">
					<h1><span><?php _e( 'Add new letter', 'sender' ); ?></span> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h1>
					<table id="sndrpr-mail-send-table" class="sndrpr-page-table">
						<tr>
							<td>
								<label class="sndrpr-form-label"><?php _e( 'Subject:', 'sender' ); ?> 
								<input disabled class="sndrpr-tltle" type="text" name="sndrpr_subject" value=""/></label>
							</td>
						</tr>
						<tr>
							<td>
								<div class="sndrpr-letter-custom">
									<label class="sndrpr-form-label"><?php _e( 'Select letter template:', 'sender' ); ?></label>
									<ul class="sndrpr-select-template">
										<li class="sndrpr-default-templates"><?php _e( 'Default Templates:', 'sender' ); ?></li>
										<li class="sndrpr-template-info">
											<label for="sndrpr-radio-1">
												<img src="<?php echo plugins_url( 'images/newsletter-icon-1.jpg', __FILE__ ); ?>" title="One Column"/>
												<p><input disabled id="sndrpr-radio-1" class="sndrpr-default-radio" type="radio" value="1"  name="sndrpr_template_id" style="display: none;"/>One Column</p>
											</label>
										</li><!-- .sndrpr-template-info -->
										<li class="sndrpr-template-info">
											<label for="sndrpr-radio-2">
												<img src="<?php echo plugins_url( 'images/newsletter-icon-2.jpg', __FILE__ ); ?>" title="Two Column: Text Below the Image"/>
												<p><input disabled id="sndrpr-radio-2" class="sndrpr-default-radio" type="radio" value="2"  name="sndrpr_template_id" style="display: none;"/>Two Column: Text Below the Image</p>
											</label>
										</li><!-- .sndrpr-template-info -->
										<li class="sndrpr-template-info">
											<label for="sndrpr-radio-3">
												<img src="<?php echo plugins_url( 'images/newsletter-icon-3.jpg', __FILE__ ); ?>" title="Two Column: Text Content Beside the Image"/>
												<p><input disabled id="sndrpr-radio-3" class="sndrpr-default-radio" type="radio" value="3"  name="sndrpr_template_id" style="display: none;"/>Two Column: Text Content Beside the Image</p>
											</label>
										</li><!-- .sndrpr-template-info -->
									</ul><!-- .sndrpr-select-template -->
								</div><!-- .sndrpr-letter-custom -->
								<div class="sndrpr-letter-custom">
									<label class="sndrpr-form-label">
										<div class="bws_help_box dashicons dashicons-editor-help">
											<div class="bws_hidden_help_text" style="min-width: 300px;">
												<p><?php _e( 'in order to use the necessary fonts you need:', 'sender' ); ?></p>
												<ol><li><?php _e( 'select the desired font (or multiple fonts), and click on the "Load Additional Data" button', 'sender' ); ?></li>
													<li><?php _e( 'click on the "Text" tab in content editor', 'sender' ); ?></li>
												<li><?php _e( 'find a necessary html-element and edit when using the attribute "style" and CSS-property "font-family" as in example', 'sender' ); ?></li>
												</ol>
												<p><?php _e( 'Example:', 'sender' ); ?></p>
												<code>&ltspan style="font-family: 'Open Sans';">Lorem ipsum set amet&lt/span></code>
											</div>
										</div>
										<?php _e( 'Select additional fonts:', 'sender' ); ?>
									</label>
									<ul class="sndrpr-fonts-list">
										<li><label><input disabled name="sndrpr_font_id[]" value="1" type="checkbox"/><span style="font-family: 'Open Sans';">Open Sans</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="2" type="checkbox"/> <span style="font-family: 'Crimson Text';">Crimson Text</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="3" type="checkbox"/> <span style="font-family: 'Josefin Slab';">Josefin Slab</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="4" type="checkbox"/> <span style="font-family: 'Arvo';">Arvo</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="5" type="checkbox"/> <span style="font-family: 'Lato';">Lato</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="6" type="checkbox"/> <span style="font-family: 'Vollkorn';">Vollkorn</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="7" type="checkbox"/> <span style="font-family: 'Abril Fatface';">Abril Fatface</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="8" type="checkbox"/> <span style="font-family: 'Ubuntu';">Ubuntu</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="9" type="checkbox"/> <span style="font-family: 'PT Sans';">PT Sans</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="10" type="checkbox"/> <span style="font-family: 'Old Standard TT';">Old Standard TT</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="11" type="checkbox"/> <span style="font-family: 'Yanone Kaffeesatz';">Yanone Kaffeesatz</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="12" type="checkbox"/> <span style="font-family: 'Roboto';">Roboto</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="13" type="checkbox"/> <span style="font-family: 'Montserrat';">Montserrat</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="14" type="checkbox"/> <span style="font-family: 'Lusitana';">Lusitana</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="15" type="checkbox"/> <span style="font-family: 'Oswald';">Oswald</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="16" type="checkbox"/> <span style="font-family: 'Lora';">Lora</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="17" type="checkbox"/> <span style="font-family: 'Raleway';">Raleway</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="18" type="checkbox"/> <span style="font-family: 'Bitter';">Bitter</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="19" type="checkbox"/> <span style="font-family: 'Cabin';">Cabin</span></label></li>
										<li><label><input disabled name="sndrpr_font_id[]" value="20" type="checkbox"/> <span style="font-family: 'Cuprum';">Cuprum</span></label></li>
									</ul><!-- .sndrpr-fonts-list -->
								</div><!-- .sndrpr-letter-custom -->
								<div class="clear"></div>
								<input disabled type="submit" id="sndrpr-load-template-button"  name="sndrpr_additional_data" value="<?php _e( 'Load Additional Data', 'sender' ); ?>"/>
								<input disabled type="submit" id="sndrpr-reset-additional-data" name="sndrpr_reset_additional_data" value="<?php _e( 'Reset Additional Data', 'sender' ); ?>"/>
							</td>
						</tr>
						<tr><td class="sndrpr-form-label">
							<div class="bws_help_box dashicons dashicons-editor-help">
								<div class="bws_hidden_help_text" style="min-width: 300px;">
									<p><?php _e( 'You can use the following shortcodes in the letter:', 'sender' ); ?></p>
									<table>
										<tr>
											<td><strong>{site_url}</strong></td>
											<td><?php _e( 'site URL', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{site_name}</strong></td>
											<td><?php _e( 'site name', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{user_email}</strong></td>
											<td><?php _e( 'user e-mail', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{user_name}</strong></td>
											<td><?php _e( 'user name', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{new_post_title}</strong></td>
											<td><?php _e( 'new post title - only for automatic mailout when publishing a new post', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{new_post_link}</strong></td>
											<td><?php _e( 'link to new post - only for automatic mailout when publishing a new post', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{profile_page}</strong></td>
											<td><?php _e( 'link to profile page of current user', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{unsubscribe_link="<i>text</i>"}</strong></td>
											<td><?php _e( 'link to unsubscribe user from mailout', 'sender' ); ?></td>
										</tr>
										<tr>
											<td><strong>{view_in_browser_link="<i>text</i>"}</strong></td>
											<td><?php _e( 'link to view letter in new browser tab', 'sender' ); ?></td>
										</tr>
									</table>
								</div>
							</div> <strong><?php _e( 'Use shortcodes!', 'sender' ); ?></strong>
						</td></tr>
						<tr>
							<td>
								<label class="sndrpr-form-label"><?php _e( 'Letter Content:', 'sender' ); ?></label>
								<div class="wp-core-ui wp-editor-wrap tmce-active"><link rel='stylesheet' id='editor-buttons-css'  href='#' type='text/css' media='all' />
									<div>
										<div class="wp-media-buttons"><a href="#" disabled class="button insert-media add_media" title="Add Media"><span class="wp-media-buttons-icon"></span> <?php _e( 'Add Media' ); ?></a></div>
										<div class="wp-editor-tabs">
											<a class="wp-switch-editor switch-html"><?php _e( 'Text' ); ?></a>
											<a class="wp-switch-editor switch-tmce"><?php _e( 'Visual' ); ?></a>
										</div>
									</div>
									<div class="wp-editor-container">
										<textarea disabled class="wp-editor-area" rows="30" autocomplete="off" cols="40" name="sndrpr_content" ></textarea>
									</div>
								</div>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input disabled type="button" class="button-small button" value="<?php _e( 'Create Mailout', 'sender' ); ?>"/>
						<input disabled type="button" class="button-small button" value="<?php _e( 'Send Test Message', 'sender' ); ?>"/>
					</p>
					<p class="submit">
						<input disabled type="submit" name="sndrpr_create_mailout" value="<?php _e( 'Create Mailout', 'sender' ); ?>" class="button-primary"/>
					</p>
				</div><!-- #sndrpr-mail .sndrpr-mail -->
			</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
				</div>
				<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
				<div class="clear"></div>
			</div>
		</div>
	<?php }
}
if ( ! function_exists( 'sndr_letters_list' ) ) {
	function sndr_letters_list() {
		global $sndr_plugin_info, $wp_version; ?>
		<div class="bws_pro_version_bloc">
			<div class="bws_pro_version_table_bloc">
				<div class="bws_table_bg" style="top:0;"></div>
				<div class="wrap sndr_letters_list" style="margin: 0 10px">
					<h1><span><?php _e( 'Letters Lists', 'sender' ); ?></span> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h1>
					<ul class='subsubsub'>
						<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 3 )</span></a> |</li>
						<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
						<input disabled type="search" name="s" value="" />
						<input disabled type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  /></p>
						<div class="tablenav top">
							<div class="alignleft actions bulkactions">
								<select disabled name='action'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_letters'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input disabled type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
						<table class="wp-list-table widefat fixed striped letters">
							<thead>
								<tr>
									<?php if ( $wp_version > '4.2') { ?>
										<td id="cb" class='manage-column column-cb check-column'>
											<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
											<input disabled type="checkbox" />
										</td>
									<?php } else { ?>
										<th scope='col' class='manage-column column-cb check-column'>
											<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
											<input disabled type="checkbox" />
										</th>
									<?php } ?>
									<th scope='col' id="subject" class='manage-column column-subject column-primary sortable desc'>
										<a href="#"><span><?php _e( 'Subject', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' id="date" class='manage-column column-date sortable desc'>
										<a href="#"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</thead>
							<tbody id="the-list" data-wp-lists='list:letter'>
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input disabled type="checkbox" name="letter_id[]" value="3" />
									</th>
									<td class="subject column-subject has-row-actions column-primary" data-colname="Subject">
										<strong><a href="#">There is a new post</a></strong>
										<div class="row-actions">
											<span class='edit_letter'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_letter'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
										<?php if ( $wp_version > '4.2') { ?>
											<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
										<?php } ?>
									</td>
									<td class='date column-date' data-colname="Date">2014-05-27 14:26:47</td>
								</tr>
								<tr>
									<th scope="row" class="check-column">
										<input disabled type="checkbox" name="letter_id[]" value="3" />
									</th>
									<td class='subject column-subject has-row-actions column-primary' data-colname="Subject">
										<strong><a href="#">Get 30% Discount</a></strong>
										<div class="row-actions">
											<span class='edit_letter'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_letter'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
										<?php if ( $wp_version > '4.2') { ?>
											<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
										<?php } ?>
									</td>
									<td class='date column-date' data-colname="Date">2014-05-27 14:26:47</td>
								</tr>
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input disabled type="checkbox" name="letter_id[]" value="3" />
									</th>
									<td class='subject column-subject has-row-actions column-primary' data-colname="Subject">
										<strong><a href="#">Test letter</a></strong>
										<div class="row-actions">
											<span class='edit_letter'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_letter'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
										<?php if ( $wp_version > '4.2') { ?>
											<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
										<?php } ?>
									</td>
									<td class='date column-date' data-colname="Date">2014-05-27 14:26:47</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<?php if ( $wp_version > '4.2') { ?>
										<td class='manage-column column-cb check-column'>
											<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
											<input disabled type="checkbox" />
										</td>
									<?php } else { ?>
										<th scope='col' class='manage-column column-cb check-column'>
											<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
											<input disabled type="checkbox" />
										</th>
									<?php } ?>
									<th scope='col' class='manage-column column-subject column-primary sortable desc'>
										<a href="#"><span><?php _e( 'Subject', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-date sortable desc'>
										<a href="#"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</tfoot>
						</table>
						<div class="tablenav bottom">
						<div class="alignleft actions bulkactions">
							<select disabled name='action2'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_letters'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" id="doaction2" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
				</div><!-- .wrap -->
			</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
				</div>
				<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
				<div class="clear"></div>
			</div>
		</div>
	<?php }
}
if ( ! function_exists( 'sndr_distribution_list' ) ) {
	function sndr_distribution_list() {
		global $sndr_plugin_info, $wp_version; ?>
		<div class="bws_pro_version_bloc">
			<div class="bws_pro_version_table_bloc">
				<div class="bws_table_bg" style="top:0;"></div>
				<div class="wrap sndr_distribution_list" style="margin: 0 10px">
					<h1><span><?php _e( 'Mailings Lists', 'sender' ); ?></span> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h1>
					<ul class='subsubsub'>
						<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 3 )</span></a> |</li>
						<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
						<input disabled type="search" name="s" value="" />
						<input disabled type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  />
					</p>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select disabled name='action'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_distributions'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
					<table class="wp-list-table widefat fixed striped mailings">
						<thead>
							<tr>
								<?php if ( $wp_version > '4.2') { ?>
									<td id="cb" class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</td>
								<?php } else { ?>
									<th scop='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</th>
								<?php } ?>
								<th scope='col' id="name" class='manage-column column-name column-primary sortable desc'>
									<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
								<th scope='col' id="date" class='manage-column column-date sortable desc'>
									<a href="#"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
							</tr>
						</thead>
						<tbody id="the-list" data-wp-lists='list:mailing'>
							<tr class="alternate">
								<th scope="row" class="check-column"><input disabled type="checkbox" name="distribution_id[]" value=" 3" /></th>
								<td class="name column-name has-row-actions column-primary" data-colname="Name">
									<strong><a href="#">Clients</a></strong> 
									<div class="row-actions">
										<span class='edit_distribution'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_distribution'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='date column-date' data-colname="Date">2014-05-27 13:32:50</td>
							</tr>
							<tr>
								<th scope="row" class="check-column"><input disabled type="checkbox" name="distribution_id[]" value=" 2" /></th>
								<td class="name column-name has-row-actions column-primary" data-colname="Name">
									<strong><a href="#">Subscribers</a></strong> 
									<div class="row-actions">
										<span class='edit_distribution'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_distribution'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='date column-date' data-colname="Date">2014-05-27 13:32:18</td>
							</tr>
							<tr class="alternate">
								<th scope="row" class="check-column"><input disabled type="checkbox" name="distribution_id[]" value=" 1" /></th>
								<td class="name column-name has-row-actions column-primary" data-colname="Name">
									<strong><a href="#">Administrators</a></strong> 
									<div class="row-actions">
										<span class='edit_distribution'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_distribution'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='date column-date' data-colname="Date">2014-05-27 12:47:21</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<?php if ( $wp_version > '4.2') { ?>
									<td class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</td>
								<?php } else { ?>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</th>
								<?php } ?>
								<th scope='col' class='manage-column column-name column-primary sortable desc'>
									<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
								<th scope='col' class='manage-column column-date sortable desc'>
									<a href="#"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
							</tr>
						</tfoot>
					</table>
					<div class="tablenav bottom">
						<div class="alignleft actions bulkactions">
							<select disabled name='action2'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_distributions'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" id="doaction2" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
				</div><!-- .wrap  -->
			</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
				</div>
				<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
				<div class="clear"></div>
			</div>
		</div>
	<?php }
}
if ( ! function_exists( 'sndr_letter_templates' ) ) {
	function sndr_letter_templates() {
		global $sndr_plugin_info, $wp_version; ?>
		<div class="bws_pro_version_bloc">
			<div class="bws_pro_version_table_bloc">
				<div class="bws_table_bg" style="top:0;"></div>
				<div class="wrap sndrpr-letter-template-page" style="margin: 0 10px">
					<h1><span><?php _e( 'Letter Templates', 'sender' ); ?></span> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h1>
					<ul class='subsubsub'>
						<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 4 )</span></a> |</li>
						<li class='default'><a href="#"><?php _e( 'Default templates', 'sender' ); ?><span> ( 3 )</span></a> |</li>
						<li class='user'><a href="#"><?php _e( 'User`s templates', 'sender' ); ?><span> ( 1 )</span></a> |</li>
						<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
						<input disabled type="search" name="s" value="" />
						<input disabled type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  />
					</p>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select disabled name='action'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_templates'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" id="doaction" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>" />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">4 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
					<table class="wp-list-table widefat fixed striped templates">
						<thead>
							<tr>
								<?php if ( $wp_version > '4.2') { ?>
									<td id="cb" class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</td>
								<?php } else { ?>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</th>
								<?php } ?>
								<th scope='col' id='title' class='manage-column column-title column-primary sortable desc'>
									<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
								<th scope='col' id='status' class='manage-column column-status sortable desc'>
									<a href="#"><span><?php _e( 'Status', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
							</tr>
						</thead>
						<tbody id="the-list" data-wp-lists='list:template'>
							<tr class="alternate">
								<th scope="row" class="check-column">
									<input disabled id="cb_1" type="checkbox" name="template_id[]" value=" 1" />
								</th>
								<td class="title column-title has-row-actions column-primary" data-colname="Title">
									<strong><a href="#">One Column</a></strong> 
									<div class="row-actions">
										<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='status column-status' data-colname="Status"><i><?php _e( 'default', 'sender' ); ?></i></td>
							</tr>
							<tr>
								<th scope="row" class="check-column">
									<input disabled type="checkbox" name="template_id[]" value=" 2" />
								</th>
								<td class="title column-title has-row-actions column-primary" data-colname="Title">
									<strong><a href="#">Two Column: Text Below the Image</a></strong> 
									<div class="row-actions">
										<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='status column-status' data-colname="Status"><i><?php _e( 'default', 'sender' ); ?></i></td>
							</tr>
							<tr class="alternate">
								<th scope="row" class="check-column">
									<input disabled type="checkbox" name="template_id[]" value=" 3" />
								</th>
								<td class="title column-title has-row-actions column-primary" data-colname="Title">
									<strong><a href="#">Two Column: Text Content Beside the Image</a></strong> 
									<div class="row-actions">
										<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='status column-status' data-colname="Status"><i><?php _e( 'default', 'sender' ); ?></i></td>
							</tr>
							<tr>
								<th scope="row" class="check-column"><input disabled type="checkbox" name="template_id[]" value=" 4" /></th>
								<td class="title column-title has-row-actions column-primary" data-colname="Title">
									<strong><a href="#">My Custom Template</a></strong> 
									<div class="row-actions">
										<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='status column-status' data-colname="Status"><i><?php _e( 'user`s', 'sender' ); ?></i></td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<?php if ( $wp_version > '4.2') { ?>
									<td class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</td>
								<?php } else { ?>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</th>
								<?php } ?>
								<th scope='col' id='title' class='manage-column column-title column-primary sortable desc'>
									<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
								<th scope='col' id='status' class='manage-column column-status sortable desc'>
									<a href="#"><span><?php _e( 'Status', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
							</tr>
						</tfoot>
					</table>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select disabled name='action'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_templates'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" id="doaction" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">4 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
				</div><!-- .wrap .sndrpr-letter-template-page -->
			</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
				</div>
				<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
				<div class="clear"></div>
			</div>
		</div>
	<?php }
}
if ( ! function_exists( 'sndr_priorities' ) ) {
	function sndr_priorities() {
		global $sndr_plugin_info, $wp_version; ?>
		<div class="bws_pro_version_bloc">
			<div class="bws_pro_version_table_bloc">
				<div class="bws_table_bg" style="top:0;"></div>
				<div class="wrap sndrpr-priorities-list-page" style="margin: 0 10px">
					<h1><span><?php _e( 'Priorities', 'sender' ); ?></span> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h1>
					<ul class='subsubsub'>
						<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 6 )</span></a> |</li>
						<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
					</ul>
					<p class="search-box">
						<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
						<input disabled type="search" name="s" value="" />
						<input disabled type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  />
					</p>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select disabled name='action'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_priorities'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">6 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
					<table class="wp-list-table widefat fixed striped priorities">
						<thead>
							<tr>
								<?php if ( $wp_version > '4.2') { ?>
									<td id="cb" class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</td>
								<?php } else { ?>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</th>
								<?php } ?>
								<th scope='col' id="title" class='manage-column column-title column-primary sortable desc'>
									<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
								<th scope='col' id="number" class='manage-column column-number sortable desc'>
									<a href="#"><span><?php _e( 'Number', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
							</tr>
						</thead>
						<tbody id="the-list" data-wp-lists='list:priority'>
							<tr class="alternate">
								<th scope="row" class="check-column"><input disabled type="checkbox" name="priority_id[]" value="6" /></th>
								<td class="title column-title has-row-actions column-primary" data-colname="Title">
									<strong><a href="#">Other message</a></strong> 
									<div class="row-actions">
										<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='number column-number' data-colname="Number">60</td>
							</tr>
							<tr>
								<th scope="row" class="check-column"><input disabled type="checkbox" name="priority_id[]" value="5" /></th>
								<td class='title column-title has-row-actions column-primary' data-colname="Title">
									<strong><a href="#">Service message</a></strong> 
									<div class="row-actions">
										<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='number column-number' data-colname="Number">50</td>
							</tr>
							<tr class="alternate">
								<th scope="row" class="check-column"><input disabled type="checkbox" name="priority_id[]" value="4" /></th>
								<td class='title column-title has-row-actions column-primary' data-colname="Title">
									<strong><a href="#">Message of congratulations</a></strong> 
									<div class="row-actions">
										<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='number column-number' data-colname="Number">40</td>
							</tr>
							<tr>
								<th scope="row" class="check-column"><input disabled type="checkbox" name="priority_id[]" value=" 3" /></th>
								<td class='title column-title has-row-actions column-primary' data-colname="Title">
									<strong><a href="#">Advertising message</a></strong> 
									<div class="row-actions">
										<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='number column-number' data-colname="Number">30</td>
							</tr>
							<tr class="alternate">
								<th scope="row" class="check-column"><input disabled type="checkbox" name="priority_id[]" value=" 2" /></th>
								<td class='title column-title has-row-actions column-primary' data-colname="Title">
									<strong><a href="#">Special offer</a></strong> 
									<div class="row-actions">
										<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='number column-number' data-colname="Number">20</td>
							</tr>
							<tr>
								<th scope="row" class="check-column"><input disabled type="checkbox" name="priority_id[]" value=" 1" /></th>
								<td class='title column-title has-row-actions column-primary' data-colname="Title">
									<strong><a href="#">Urgent message</a></strong> 
									<div class="row-actions">
										<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
										<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
									</div>
									<?php if ( $wp_version > '4.2') { ?>
										<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button> 
									<?php } ?>
								</td>
								<td class='number column-number' data-colname="Number">10</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<?php if ( $wp_version > '4.2') { ?>
									<td class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</td>
								<?php } else { ?>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input disabled type="checkbox" />
									</th>
								<?php } ?>
								<th scope='col' class='manage-column column-title column-primary sortable desc'>
									<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
								<th scope='col' class='manage-column column-number sortable desc'>
									<a href="#"><span><?php _e( 'Number', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
								</th>
							</tr>
						</tfoot>
					</table>
					<div class="tablenav bottom">
						<div class="alignleft actions bulkactions">
							<select disabled name='action2'>
								<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
								<option value='trash_priorities'><?php _e( 'Trash', 'sender' ); ?></option>
							</select>
							<input disabled type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
						</div>
						<div class='tablenav-pages one-page'><span class="displaying-num">6 <?php _e( 'items', 'sender' ); ?></span></div>
						<br class="clear" />
					</div>
				</div><!-- .wrap .sndrpr-priorities-list-page -->
			</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to Pro version', 'sender' ); ?> 
				</div>
				<a class="bws_button" href="http://bestwebsoft.com/products/sender/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>
				<div class="clear"></div>
			</div>
		</div>
	<?php }
}


/**
 * Check if plugin Subscriber by BestWebSoft is installed
 * @return bool  true if Subscriber is installed
 */
if ( ! function_exists( 'sndr_check_subscriber_install' ) ) {
	function sndr_check_subscriber_install() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugins_list = get_plugins();
		if ( array_key_exists( 'subscriber-pro/subscriber-pro.php', $plugins_list ) || array_key_exists( 'subscriber/subscriber.php', $plugins_list ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Performed at deactivation.
 * @return void
 */
if ( ! function_exists( 'sndr_send_deactivate' ) ) {
	function sndr_send_deactivate() {
		/* Delete cron hook */
		wp_clear_scheduled_hook( 'sndr_mail_hook' );
	}
}

/**
 * Performed at uninstal.
 * @return void
 */
if ( ! function_exists( 'sndr_send_uninstall' ) ) {
	function sndr_send_uninstall() {
		global $wpdb;
		wp_clear_scheduled_hook( 'sndr_mail_hook' );

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugins_list = get_plugins();

		$check_sender_pro_install = ( array_key_exists( 'sender-pro/sender-pro.php', $plugins_list ) ) ? true : false;
		$check_subscriber_install = ( array_key_exists( 'subscriber-pro/subscriber-pro.php', $plugins_list ) || array_key_exists( 'subscriber/subscriber.php', $plugins_list ) ) ? true : false;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'sndr_options' );
				$prefix = 1 == $blog_id ? $wpdb->base_prefix : $wpdb->base_prefix . $blog_id . '_';
				if ( ! $check_sender_pro_install ) {
					/* delete plugin`s tables except "sndr_mail_users_info" */
					$wpdb->query( "DROP TABLE IF EXISTS
						`" . $prefix . "sndr_mail_send`, 
						`" . $prefix . "sndr_users`,
						`" . $prefix . "sndr_distribution_lists`, 
						`" . $prefix . "sndr_mailout`, 
						`" . $prefix . "sndr_mail_priority`, 
						`" . $prefix . "sndr_fonts`, 
						`" . $prefix . "sndr_thumbnails`, 
						`" . $prefix . "sndr_mail_templates`;" 
					);
				}
				if ( ! $check_subscriber_install && ! $check_sender_pro_install ) {
					$wpdb->query( "DROP TABLE IF EXISTS `" . $prefix . "sndr_mail_users_info`" );
				}
			}
			switch_to_blog( $old_blog );
			delete_site_option( 'sndr_options' );
		} else {
			delete_option( 'sndr_options' );
			if ( ! $check_sender_pro_install ) {
				/* delete plugin`s tables except "sndr_mail_users_info" */
				$wpdb->query( "DROP TABLE IF EXISTS
					`" . $wpdb->prefix . "sndr_mail_send`, 
					`" . $wpdb->prefix . "sndr_users`,
					`" . $wpdb->prefix . "sndr_distribution_lists`, 
					`" . $wpdb->prefix . "sndr_mailout`, 
					`" . $wpdb->prefix . "sndr_mail_priority`, 
					`" . $wpdb->prefix . "sndr_fonts`, 
					`" . $wpdb->prefix . "sndr_thumbnails`, 
					`" . $wpdb->prefix . "sndr_mail_templates`;" 
				);
			}
			if ( ! $check_subscriber_install && ! $check_sender_pro_install ) {
				$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "sndr_mail_users_info`" );
			}
		}
		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

if ( ! function_exists ( 'sndr_plugin_banner' ) ) {
	function sndr_plugin_banner() {
		global $hook_suffix, $sndr_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {
			if ( ( is_multisite() && is_network_admin() ) || ! is_multisite() ) {
				global $sndr_options;
				if ( empty( $sndr_options ) )
					$sndr_options = is_multisite() ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );

				if ( isset( $sndr_options['first_install'] ) && strtotime( '-1 week' ) > $sndr_options['first_install'] )
					bws_plugin_banner( $sndr_plugin_info, 'sndr', 'sender', 'c273031fe5f64b4ea95f2815ae9313b5', '114', '//ps.w.org/sender/assets/icon-128x128.png' ); 
			
				bws_plugin_banner_to_settings( $sndr_plugin_info, 'sndr_options', 'sender', 'admin.php?page=sndr_settings', 'admin.php?page=sndr_send_user' );
			}

			if ( is_multisite() && ! is_network_admin() && is_admin() ) { ?>
				<div class="update-nag"><strong><?php _e( 'Notice:', 'sender' ); ?></strong> 
					<?php if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						_e( 'Due to the peculiarities of the multisite work, Sender plugin has only', 'sender' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'admin.php?page=sndr_settings' ); ?>"><?php _e( 'Network settings page', 'sender' ); ?></a>
					<?php } else {
						_e( 'Due to the peculiarities of the multisite work, Sender plugin has the network settings page only and it should be Network Activated. Please', 'sender' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate Sender for Network', 'sender' ); ?></a>
					<?php } ?>
				</div>
			<?php }
		}
		if ( isset( $_REQUEST['page'] ) && 'sndr_settings' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $sndr_plugin_info, 'sndr_options', 'sender' );
		}
	}
}

/**
 * Add all hooks
 */
register_activation_hook( plugin_basename( __FILE__ ), 'sndr_send_activate' );
if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() )
		add_action( 'network_admin_menu', 'sndr_admin_default_setup' );
	else
		add_action( 'admin_menu', 'sndr_admin_default_setup' );
}
add_action( 'plugins_loaded', 'sndr_plugins_loaded' );

add_filter( 'plugin_action_links', 'sndr_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'sndr_register_plugin_links', 10, 2 );

add_action( 'profile_personal_options', 'sndr_mail_send' );
add_action( 'user_register', 'sndr_mail_register_user' );

add_action( 'init', 'sndr_init' );
add_action( 'admin_init', 'sndr_admin_init' );
add_action( 'admin_enqueue_scripts', 'sndr_admin_head' );
add_action( 'profile_update', 'sndr_update', 10, 2 );
add_filter( 'cron_schedules', 'sndr_more_reccurences' );
add_action( 'sndr_mail_hook', 'sndr_cron_mail' );
add_filter( 'set-screen-option', 'sndr_table_set_option', 10, 3 );

add_action( 'admin_notices', 'sndr_plugin_banner' );
add_action( 'network_admin_notices', 'sndr_plugin_banner' );

add_action( 'mlq_change_status_on_sender_mail', 'sndr_get_update_on_mail_from_email_queue', 10, 3 );

register_deactivation_hook( plugin_basename( __FILE__ ), 'sndr_send_deactivate' );
register_uninstall_hook( plugin_basename( __FILE__ ), 'sndr_send_uninstall' );