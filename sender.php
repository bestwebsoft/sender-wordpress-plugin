<?php
/*
Plugin Name: Sender
Plugin URI: http://bestwebsoft.com/plugin/
Description: This plugin send mail to registered users.
Author: BestWebSoft
Version: 0.9
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2014  BestWebSoft  ( http://support.bestwebsoft.com )

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
		global $wp_version, $bstwbsftwppdtplgns_options, $wpmu, $bstwbsftwppdtplgns_added_menu;
		$bws_menu_info = get_plugin_data( plugin_dir_path( __FILE__ ) . "bws_menu/bws_menu.php" );
		$bws_menu_version = $bws_menu_info["Version"];
		$base = plugin_basename( __FILE__ );

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			if ( 1 == $wpmu ) {
				if ( ! get_site_option( 'bstwbsftwppdtplgns_options' ) )
					add_site_option( 'bstwbsftwppdtplgns_options', array(), '', 'yes' );
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				if ( ! get_option( 'bstwbsftwppdtplgns_options' ) )
					add_option( 'bstwbsftwppdtplgns_options', array(), '', 'yes' );
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}

		if ( isset( $bstwbsftwppdtplgns_options['bws_menu_version'] ) ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			unset( $bstwbsftwppdtplgns_options['bws_menu_version'] );
			update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] ) || $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] < $bws_menu_version ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_added_menu ) ) {
			$plugin_with_newer_menu = $base;
			foreach ( $bstwbsftwppdtplgns_options['bws_menu']['version'] as $key => $value ) {
				if ( $bws_menu_version < $value && is_plugin_active( $base ) ) {
					$plugin_with_newer_menu = $key;
				}
			}
			$plugin_with_newer_menu = explode( '/', $plugin_with_newer_menu );
			$wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? basename( WP_CONTENT_DIR ) : 'wp-content';
			if ( file_exists( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' ) )
				require_once( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' );
			else
				require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
			$bstwbsftwppdtplgns_added_menu = true;			
		}

		$icon_path    = $wp_version < 3.8 ? plugins_url( "images/plugin_icon_37.png",  __FILE__ ) : plugins_url( "images/plugin_icon_38.png",  __FILE__ );
		$capabilities = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_menu_page( 'BWS Plugins', 'BWS Plugins', $capabilities, 'bws_plugins',  'bws_add_menu_render', plugins_url( "images/px.png", __FILE__ ), 1001 );
		add_submenu_page( 'bws_plugins', __( 'Sender', 'sender'), __( 'Sender', 'sender' ), $capabilities, 'sndr_settings', 'sndr_admin_settings_content' );
		add_menu_page( __( 'Sender', 'sender' ), __( 'Sender', 'sender' ), $capabilities, 'sndr_send_user', 'sndr_admin_mail_send', $icon_path, 32 );
		$hook = add_submenu_page( 'sndr_send_user', __( 'Reports', 'sender' ), __( 'Reports', 'sender' ), $capabilities, 'view_mail_send', 'sndr_mail_view' );		
		add_action( "load-$hook", 'sndr_screen_options' );

		/* pro pages */
		add_submenu_page( 'sndr_send_user', __( 'New Mailout', 'sender' ), __( 'New Mailout', 'sender' ), $capabilities, 'sndrpr_create_mailout', 'sndr_create_mailout' );
		add_submenu_page( 'sndr_send_user', __( 'Letters', 'sender' ), __( 'Letters', 'sender' ), $capabilities, 'sndrpr_letters_list', 'sndr_letters_list' );
		add_submenu_page( 'sndr_send_user', __( 'Mailings Lists', 'sender' ), __( 'Mailings Lists', 'sender' ), $capabilities, 'sndrpr_distribution_list', 'sndr_distribution_list' );
		add_submenu_page( 'sndr_send_user', __( 'Letters Templates', 'sender' ), __( 'Letters Templates', 'sender' ), $capabilities, 'sndrpr_letter_templates', 'sndr_letter_templates' );
		add_submenu_page( 'sndr_send_user', __( 'Priorities', 'sender' ), __( 'Priorities', 'sender' ), $capabilities, 'sndrpr_priorities', 'sndr_priorities' );
	}
}

/**
 * Plugin functions for init
 * @return void
 */
if ( ! function_exists ( 'sndr_admin_init' ) ) {
	function sndr_admin_init() {
		global $bws_plugin_info, $sndr_plugin_info;
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'sender', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		if ( ! $sndr_plugin_info )
			$sndr_plugin_info = get_plugin_data( __FILE__ );

		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '114', 'version' => $sndr_plugin_info["Version"] );

		/* check WordPress version */
		sndr_version_check();

		if ( isset( $_REQUEST['page'] ) && ( 'sndr_send_user' == $_REQUEST['page'] || 'view_mail_send' == $_REQUEST['page'] || 'sndr_settings' == $_REQUEST['page'] ) ) {
			/* register plugin settings */
			sndr_register_settings();
			/* Redirect to "report" page */
			sndr_redirect();
		}
	}
}

/**
 * Function check if plugin is compatible with current WP version
 * @return void
 */
if ( ! function_exists ( 'sndr_version_check' ) ) {
	function sndr_version_check() {
		global $wp_version, $sndr_plugin_info;
		$require_wp		=	"3.1"; /* Wordpress at least requires version */
		$plugin			=	plugin_basename( __FILE__ );
	 	if ( version_compare( $wp_version, $require_wp, "<" ) ) {
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				wp_die( "<strong>" . $sndr_plugin_info['Name'] . " </strong> " . __( 'requires', 'sender' ) . " <strong>WordPress " . $require_wp . "</strong> " . __( 'or higher, that is why it has been deactivated! Please upgrade WordPress and try again.', 'sender') . "<br /><br />" . __( 'Back to the WordPress', 'sender') . " <a href='" . get_admin_url( null, 'plugins.php' ) . "'>" . __( 'Plugins page', 'sender') . "</a>." );
			}
		}
	}
}

/**
 * Register settings function
 * @return void
 */
if ( ! function_exists( 'sndr_register_settings' ) ) {
	function sndr_register_settings() {
		global $wpmu, $wpdb, $sndr_options, $sndr_options_default, $sndr_plugin_info;
		$sndr_db_version = '0.3';

		$admin_email = get_bloginfo( 'admin_email' );
		$admin_data  = get_user_by( 'email', $admin_email );
		if ( ! $admin_data ) {
			$admin_list  = get_super_admins();
			$admin_login = $admin_list[0];
		} else {
			$admin_login = $admin_data->user_login;
		}

		$sndr_options_default = array(
			'plugin_option_version' 	=> $sndr_plugin_info["Version"],
			'plugin_db_version' 		=> $sndr_db_version,
			'sndr_run_time'          	=> 1,
			'sndr_send_count'        	=> 2,
			'sndr_select_from_field' 	=> 'admin_name', /* <input type="radio"/> chosen user name or custom  name */
			'sndr_from_admin_name'   	=> $admin_login, /* <select> admin list */
			'sndr_from_custom_name'  	=> get_bloginfo( 'name' ), /* custom name in field 'From' */
			'sndr_from_email'        	=> $admin_email,  /* admin email	*/		
			'sndr_display_options'   	=> false,
			'sndr_method'            	=> 'mail',
			'sndr_smtp_settings'     	=> array( 
				'host'               	=> 'smtp.example.com',
				'accaunt'            	=> 'youraccaunt',
				'password'          	=> 'yourpassword',
				'port'              	=> 25,
				'ssl'               	=> true
			)
		);


		/* install the default plugin options */
		if ( 1 == $wpmu ) {
			if ( ! get_site_option( 'sndr_options' ) )
				add_site_option( 'sndr_options', $sndr_options_default, '', 'yes' );
		} else {
			if ( ! get_option( 'sndr_options' ) )
				add_option( 'sndr_options', $sndr_options_default, '', 'yes' );
		}

		/* get plugin options from the database */
		$sndr_options = is_multisite() ? get_site_option( 'sndr_options' ) : $sndr_options = get_option( 'sndr_options' );

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

			if ( 0 == $colum_exists )
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` CHANGE `done` `mail_status` INT( 1 ) NOT NULL DEFAULT '0';" );

			/* added "remote_delivery" column */
			$colum_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "sndr_mail_send` LIKE 'remote_delivery';" );

			if ( 0 == $colum_exists )
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "sndr_mail_send` ADD `remote_delivery` INT(1) NOT NULL DEFAULT '0';" );

			$sndr_options['plugin_db_version'] = $sndr_db_version;

			if ( is_multisite() )			
				update_site_option( 'sndr_options', $sndr_options );
			else
				update_option( 'sndr_options', $sndr_options );
		}

		if ( ! isset( $sndr_options['plugin_option_version'] ) || $sndr_options['plugin_option_version'] != $sndr_plugin_info["Version"] ) {

			/* array merge incase new version of plugin has added new options */
			$sndr_options = array_merge( $sndr_options_default, $sndr_options );
			$sndr_options['plugin_option_version'] = $sndr_plugin_info["Version"];

			/*  change value of some plugin options ( for V0.1 ) */
			if ( 'admin_name' != $sndr_options['sndr_select_from_field'] && 'custom_name' != $sndr_options['sndr_select_from_field'] )
				$sndr_options['sndr_select_from_field'] = 'admin_name';

			if ( ! ( empty( $sndr_options['sndr_from_email'] ) && is_email( $sndr_options['sndr_from_email'] ) ) )
				$sndr_options['sndr_from_email'] = $admin_email;
			/* end of update for V0.1 */

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
		/* Static so we don't call plugin_basename on every plugin row. */
		static $this_plugin;
		if ( ! $this_plugin )
			$this_plugin = plugin_basename( __FILE__ );
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=sndr_settings">' . __( 'Settings', 'sender' ) . '</a>';
			array_unshift( $links, $settings_link );
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
			$links[] = '<a href="admin.php?page=sndr_settings">' . __( 'Settings', 'sender' ) . '</a>';
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
		} else { /* Add users data which were not insertet in plugin tables */
			if ( function_exists( 'sbscrbr_users_list' ) ) { /* if Subscriber plugin already installed and activated */
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
	}
}

/**
 * Function to add plugin scripts
 * @return void
 */
if ( ! function_exists ( 'sndr_admin_head' ) ) {
	function sndr_admin_head() {
		global $wp_version;
		if ( 3.8 > $wp_version )
			wp_enqueue_style( 'sndr_stylesheet', plugins_url( 'css/styles_wp_before_3.8.css', __FILE__ ) );	
		else
			wp_enqueue_style( 'sndr_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
		
		if ( isset( $_REQUEST['page'] ) && ( 'sndr_send_user' == $_REQUEST['page'] || 'view_mail_send' == $_REQUEST['page'] || 'sndr_settings' == $_REQUEST['page'] ) ) {
			$script_vars = array(
				'closeReport'   => __( 'Close Report', 'sender' ),
				'showReport'    => __( 'Show Report', 'sender' ),
				'emptyReport'   => __( "The data of this report can't be found.", 'sender' ),
				'badRequest'    => __( 'Error while sending request.', 'sender' ),
				'toLongMessage' => __( 'Are you sure that you want to enter such a large value?', 'sender' )
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
		global $wp_version, $wpdb, $wpmu, $sndr_options, $sndr_options_default, $title, $sndr_plugin_info;
		$display_add_options = $message = $error = '';

		if ( empty( $sndr_options ) ) {
			$sndr_options = ( 1 == $wpmu ) ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );
		}

		$admin_list = $wpdb->get_results( 
			"SELECT DISTINCT `user_login` , `display_name` FROM `" . $wpdb->prefix . "users` 
				LEFT JOIN `" . $wpdb->prefix . "usermeta` ON `" . $wpdb->prefix . "usermeta`.`user_id` = `" . $wpdb->prefix . "users`.`ID` 
			WHERE `meta_value` LIKE  '%administrator%'",
			ARRAY_A
		);

		if ( isset( $_POST['sndr_form_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sndr_nonce_name' ) ) {	
			// update settings to send messages
			// check value from "Interval for sending mail" option
			if ( isset( $_POST['sndr_mail_run_time'] ) ) {
				if ( empty( $_POST['sndr_mail_run_time'] ) || 1 > intval( $_POST['sndr_mail_run_time'] ) || ! preg_match( '/^\d+$/', $_POST['sndr_mail_run_time'] ) ) {
					$sndr_options['sndr_run_time'] = '1';
				} else {
					if ( 360 < $_POST['sndr_mail_run_time'] ) {
						$message .= __( 'You may have entered too large a value in the "Interval for sending mail" option. Check please.', 'sender' ) . '<br/>';
					}
					$sndr_options['sndr_run_time'] = $_POST['sndr_mail_run_time'];
				}
				add_filter( 'cron_schedules', 'sndr_more_reccurences' );
			} else {
				$sndr_options['sndr_run_time'] = $sndr_options_default['sndr_run_time'];
			}
			// check value from "Number of messages sent at one time" option
			if ( isset( $_POST['sndr_mail_send_count'] ) ) {
				if ( empty( $_POST['sndr_mail_send_count'] ) || 1 > intval( $_POST['sndr_mail_send_count'] ) || ! preg_match( '/^\d+$/', $_POST['sndr_mail_send_count'] ) ) {
					$sndr_options['sndr_send_count'] = '1';
				} else {
					if ( 50 < $_POST['sndr_mail_send_count'] ) {
						$message .= __( 'You may have entered too large a value in the "Number of sent messages at one time" option. Check please.', 'sender' ) . '<br/>';
					}
					$sndr_options['sndr_send_count'] = $_POST['sndr_mail_send_count'];
				}
			} else {
				$sndr_options['sndr_send_count'] = $sndr_options_default['sndr_send_count'];
			}

			$sndr_options['sndr_select_from_field'] = isset( $_POST['sndr_select_from_field'] ) ? $_POST['sndr_select_from_field'] : $sndr_options_default['sndr_select_from_field'];
			if ( 'admin_name' == $sndr_options['sndr_select_from_field'] ) {
				if ( '3.3' > $wp_version 
					&& function_exists( 'get_userdatabylogin' ) 
					&& false != get_userdatabylogin( $_POST['sndr_from_admin_name'] ) ) {
					$sndr_options['sndr_from_admin_name'] = isset( $_POST['sndr_from_admin_name'] ) ? $_POST['sndr_from_admin_name'] : $sndr_options_default['sndr_from_admin_name'];
					$user_data                            = get_userdatabylogin( $sndr_options['sndr_from_admin_name'] );
					$sndr_options['sndr_from_email']      = $user_data->user_email;
				} elseif ( false != get_user_by( 'login', $_POST['sndr_from_admin_name'] ) ) {
					$sndr_options['sndr_from_admin_name'] = isset( $_POST['sndr_from_admin_name'] ) ? $_POST['sndr_from_admin_name'] : $sndr_options_default['sndr_from_admin_name'];
					$user_data                            = get_user_by( 'login', $sndr_options['sndr_from_admin_name'] );
					$sndr_options['sndr_from_email']      = $user_data->user_email;
				} else {
					$error .= __(  "Such a user does not exist. Settings are not saved.", 'sender' );
				}
			} else {
				if ( isset( $_POST['sndr_from_email'] ) ) {
					if ( is_email( trim( $_POST['sndr_from_email'] ) ) )
						$sndr_options['sndr_from_email']  = trim( $_POST['sndr_from_email'] );
					else
						$error .= __( "Please enter a valid email address in the 'FROM' field. Settings are not saved.", 'sender' );
				} else
					$sndr_options['sndr_from_email']  = $sndr_options_default['sndr_from_email'];
			}
			if ( empty( $sndr_options['sndr_from_email'] ) ) {
				$sndr_options['sndr_from_email'] = $sndr_options_default['sndr_from_email'];
			}

			$sndr_options['sndr_from_custom_name']  = isset( $_POST['sndr_from_custom_name'] ) ? stripslashes( esc_html( $_POST['sndr_from_custom_name'] ) ) : $sndr_options_default['sndr_from_custom_name'];

			$sndr_options['sndr_display_options'] = ( isset( $_POST['sndr_additions_options'] ) ) ? true : false;

			if ( $sndr_options['sndr_display_options'] ) {

				$sndr_options['sndr_method'] = $_POST['sndr_mail_method'];
				if ( $_POST['sndr_mail_method'] == 'smtp' ) {
					$sndr_options['sndr_smtp_settings']['host']     	= stripslashes( esc_html( $_POST['sndr_mail_smtp_host'] ) );
					$sndr_options['sndr_smtp_settings']['accaunt']  	= stripcslashes( esc_html( $_POST['sndr_mail_smtp_accaunt'] ) );
					$sndr_options['sndr_smtp_settings']['password'] 	= stripcslashes( esc_html( $_POST['sndr_mail_smtp_password'] ) );
					// check value from "SMTP port" option
					if ( isset( $_POST['sndr_mail_smtp_port'] ) ) {
						if ( empty( $_POST['sndr_mail_smtp_port'] ) || 1 > intval( $_POST['sndr_mail_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['sndr_mail_smtp_port'] ) ) ) {
							$sndr_options['sndr_smtp_settings']['port'] = '25';
						} else {
							$sndr_options['sndr_smtp_settings']['port'] = $_POST['sndr_mail_smtp_port'];
						}
					} else {
						$sndr_options['sndr_smtp_settings']['port'] = $sndr_options_default['sndr_smtp_settings']['port'];
					}
					$sndr_options['sndr_smtp_settings']['ssl'] =  ( isset( $_POST['sndr_ssl'] ) ) ? true : false ;
				}
			}
			if ( empty( $error ) ) {
				if ( is_multisite() )
					update_site_option( 'sndr_options', $sndr_options );
				else
					update_option( 'sndr_options', $sndr_options );

				$message .= __( "Settings saved.", 'sender' );	
			}			
		}

		/* GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			global $wpmu, $bstwbsftwppdtplgns_options;

			$bws_license_key = ( isset( $_POST['bws_license_key'] ) ) ? trim( stripslashes( esc_html( $_POST['bws_license_key'] ) ) ) : "";

			if ( isset( $_POST['bws_license_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_license_nonce_name' ) ) {
				if ( '' != $bws_license_key ) { 
					if ( 18 != strlen( $bws_license_key ) ) {
						$error = __( "Wrong license key", 'sender' );
					} else {
						$bws_license_plugin = trim( stripslashes( esc_html( $_POST['bws_license_plugin'] ) ) );	
						if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] ) && $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] < ( time() + (24 * 60 * 60) ) ) {
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] + 1;
						} else {
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = 1;
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] = time();
						}	

						/* download Pro */
						if ( ! function_exists( 'get_plugins' ) )
							require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
						if ( ! function_exists( 'is_plugin_active_for_network' ) )
							require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
						$all_plugins	=	get_plugins();
						$active_plugins	=	get_option( 'active_plugins' );
						
						if ( ! array_key_exists( $bws_license_plugin, $all_plugins ) ) {
							$current = get_site_transient( 'update_plugins' );
							if ( is_array( $all_plugins ) && !empty( $all_plugins ) && isset( $current ) && is_array( $current->response ) ) {
								$to_send = array();
								$to_send["plugins"][ $bws_license_plugin ] = array();
								$to_send["plugins"][ $bws_license_plugin ]["bws_license_key"] = $bws_license_key;
								$to_send["plugins"][ $bws_license_plugin ]["bws_illegal_client"] = true;
								$options = array(
									'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3 ),
									'body' => array( 'plugins' => serialize( $to_send ) ),
									'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );
								$raw_response = wp_remote_post( 'http://bestwebsoft.com/wp-content/plugins/paid-products/plugins/update-check/1.0/', $options );

								if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) ) {
									$error = __( "Something went wrong. Try again later. If the error will appear again, please, contact us <a href=http://support.bestwebsoft.com>BestWebSoft</a>. We are sorry for inconvenience.", 'sender' );
								} else {
									$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );
									
									if ( is_array( $response ) && !empty( $response ) ) {
										foreach ( $response as $key => $value ) {
											if ( "wrong_license_key" == $value->package ) {
												$error = __( "Wrong license key", 'sender' ); 
											} elseif ( "wrong_domain" == $value->package ) {
												$error = __( "This license key is bind to another site", 'sender' );
											} elseif ( "you_are_banned" == $value->package ) {
												$error = __( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'sender' );
											}
										}
										if ( '' == $error ) {																	
											$bstwbsftwppdtplgns_options[ $bws_license_plugin ] = $bws_license_key;

											$url = 'http://bestwebsoft.com/wp-content/plugins/paid-products/plugins/downloads/?bws_first_download=' . $bws_license_plugin . '&bws_license_key=' . $bws_license_key . '&download_from=5';
											$uploadDir = wp_upload_dir();
											$zip_name = explode( '/', $bws_license_plugin );
										    if ( file_put_contents( $uploadDir["path"] . "/" . $zip_name[0] . ".zip", file_get_contents( $url ) ) ) {
										    	@chmod( $uploadDir["path"] . "/" . $zip_name[0] . ".zip", octdec( 755 ) );
										    	if ( class_exists( 'ZipArchive' ) ) {
													$zip = new ZipArchive();
													if ( $zip->open( $uploadDir["path"] . "/" . $zip_name[0] . ".zip" ) === TRUE ) {
														$zip->extractTo( WP_PLUGIN_DIR );
														$zip->close();
													} else {
														$error = __( "Failed to open the zip archive. Please, upload the plugin manually", 'sender' );
													}								
												} elseif ( class_exists( 'Phar' ) ) {
													$phar = new PharData( $uploadDir["path"] . "/" . $zip_name[0] . ".zip" );
													$phar->extractTo( WP_PLUGIN_DIR );
												} else {
													$error = __( "Your server does not support either ZipArchive or Phar. Please, upload the plugin manually", 'sender' );
												}
												@unlink( $uploadDir["path"] . "/" . $zip_name[0] . ".zip" );										    
											} else {
												$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'sender' );
											}

											/* activate Pro */
											if ( file_exists( WP_PLUGIN_DIR . '/' . $zip_name[0] ) ) {			
												array_push( $active_plugins, $bws_license_plugin );
												update_option( 'active_plugins', $active_plugins );
												$pro_plugin_is_activated = true;
											} elseif ( '' == $error ) {
												$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'sender' );
											}																				
										}
									} else {
										$error = __( "Something went wrong. Try again later or upload the plugin manually. We are sorry for inconvenience.", 'sender' ); 
					 				}
					 			}
				 			}
						} else {
							/* activate Pro */
							if ( ! ( in_array( $bws_license_plugin, $active_plugins ) || is_plugin_active_for_network( $bws_license_plugin ) ) ) {			
								array_push( $active_plugins, $bws_license_plugin );
								update_option( 'active_plugins', $active_plugins );
								$pro_plugin_is_activated = true;
							}						
						}
						update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			 		}
			 	} else {
		 			$error = __( "Please, enter Your license key", 'sender' );
		 		}
		 	}
		} ?>
		<div class="sndr-mail wrap" id="sndr-mail">
			<style>
				<?php if ( ! $sndr_options['sndr_display_options'] ) { ?>
					.ad_opt {
						display: none; 
					}
				<?php }
				if ( 'smtp' != $sndr_options['sndr_method'] ) { ?>
					.sndr_smtp_options {
						display: none; 
					}
				<?php } ?>
			</style>
			<div id="icon-options-general" class="icon32 icon32-bws"></div>
			<h2><?php _e( "Sender Settings", 'sender' ); ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=sndr_settings"><?php _e( 'Settings', 'sender' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/plugin/sender/#faq" target="_blank"><?php _e( 'FAQ', 'sender' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=sndr_settings&amp;action=go_pro"><?php _e( 'Go PRO', 'sender' ); ?></a>
			</h2>
			<div class="updated fade" <?php if ( empty( $message ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! isset( $_GET['action'] ) ) { ?>
				<div id="sndr-settings-notice" class="updated fade" style="display:none"><p><strong><?php _e( "Notice:", 'sender' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'sender' ); ?></p></div>
				<form id="sndr_settings_form" method="post" action="admin.php?page=sndr_settings">
					<table id="sndr-settings-table" class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Interval for sending mail', 'sender' ); ?></th>
							<td><input id="sndr_mail_run_time" name='sndr_mail_run_time' type='text' value='<?php echo $sndr_options['sndr_run_time']; ?>'> <?php _e( '(min)', 'sender' ); ?></td>
						</tr>
						<tr>
							<th><?php _e( 'Number of messages sent at one time', 'sender' ); ?></th>
							<td>
								<input id="sndr_mail_send_count" name='sndr_mail_send_count' type='text' value='<?php echo $sndr_options['sndr_send_count']; ?>'><br/>
								<span class="sndr_info">
									<?php $number = floor( ( 60 / intval( $sndr_options['sndr_run_time'] ) ) * intval( $sndr_options['sndr_send_count'] ) );
									_e( 'maximum number of sent mails:', 'sender' );?>&nbsp;<span id="sndr-calculate"><?php echo $number; ?></span>&nbsp;<?php _e( 'per hour', 'sender' ); ?>.&nbsp;<br/><span id="sndr_calc_info"><?php _e( 'Please make sure that this number is smaller than max allowed number of sent mails from your hosting account.', 'sender' ); ?></span>
								</span>
							</td>
						</tr>
					</table>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">	
							<div class="bws_table_bg"></div>											
							<table class="form-table bws_pro_version">
								<tr valign="top">
									<th><?php _e( 'Attempts number', 'sender' ); ?></th>
									<td>
										<input name='sndrpr_max_try_count' type='text' value=''><br/>
										<span class="sndr_info">( <?php _e( 'Maximum number of attempts to send a message to a single user', 'sender' ); ?> )</span>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( "Email in HTML format sending", 'sender' ); ?></th>
									<td><input type="checkbox" name="sndrpr_html_email" value="1" /></td>
								</tr>
								<tr class="sndrpr-add-set" valign="top">
									<th><?php _e( 'Send email with confirmation', 'sender' ); ?></th>
									<td>
										<input type='checkbox' name='sndrpr_confirm' value="1" />
										<br/>
										<span  class="sndr_info">( <?php _e( "This function works not on all mail servers.", 'sender' ); ?> )</span>
									</td>
								</tr>								
								<tr valign="top">
									<th><?php _e( 'Display fonts list on "Edit letter" or "Edit Template" Pages', 'sender' ); ?></th>
									<td>
										<input type='checkbox' name='sndrpr_display_fonts_list' value="1" />
										<br/>
										<span class="sndr_info">( <?php _e( "Due to security reasons, the additional fonts may not be reflected on some postal services.", 'sender' ); ?> )</span>
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
								<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'sender' ); ?> 
								<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>			
							</div>
							<div class="bws_pro_links">
								<a class="bws_button" href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>#purchase" target="_blank" title="Sender Pro Plugin">
									<?php _e( 'Go', 'sender' ); ?> <strong>PRO</strong>
								</a>
							</div>	
							<div class="clear"></div>					
						</div>
					</div>
					<table class="form-table">
						<tr valign="top">
							<th scope="row" style="width:200px;"><?php _e( "The user on whose behalf mailout will be created", 'sender' ); ?></th>
							<td colspan="2">
								<input type="radio" id="sndr_select_from_field" name="sndr_select_from_field" value="admin_name" <?php if ( 'admin_name' == $sndr_options['sndr_select_from_field'] ) { echo "checked=\"checked\" "; } ?>/>
								<select name="sndr_from_admin_name">
									<?php foreach ( $admin_list as $user ) { ?>
										<option value="<?php echo $user['display_name']; ?>" <?php if ( $user['display_name'] == $sndr_options['sndr_from_admin_name'] ) { echo "selected=\"selected\" "; } ?>><?php echo $user['user_login']; ?></option>
									<?php } ?>
								</select>
								<span class="sndr_info">(<?php _e( "The name of the user be used in the 'From' field.", 'sender' ); ?>)</span><br/>
								<input type="radio" id="sndr_select_from_custom_field" name="sndr_select_from_field" value="custom_name" <?php if ( 'custom_name' == $sndr_options['sndr_select_from_field'] ) { echo "checked=\"checked\" "; } ?>/> 
								<input type="text" style="width:200px;" name="sndr_from_custom_name" value="<?php echo $sndr_options['sndr_from_custom_name']; ?>"/>
								<span  class="sndr_info">(<?php _e( "This text will be used in the 'FROM' field", 'sender' ); ?>)</span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" style="width:200px;"><?php _e( "User Email", 'sender' ); ?></th>
							<td colspan="2" style="position: relative;">
								<input type="text" name="sndr_from_email" value="<?php echo $sndr_options['sndr_from_email']; ?>"/>
								<span class="sndr_info">(<?php _e( "This email address will be used in the 'From' field.", 'sender' ); ?>)</span>
							</td>
						</tr>			
						<tr style="height: 45px;">
							<th>
								<label>
									<input type="checkbox" value="1" id="change_options" name="sndr_additions_options" <?php if ( $sndr_options['sndr_display_options'] ) echo 'checked="checked"'; ?> /> 
									<?php _e( 'Additional options', 'sender' ); ?>
								</label>
							</th>
						</tr>
						<tr class="ad_opt">
							<th><?php _e( 'What to use?', 'sender' ); ?></th>
							<td>
								<label>
									<input id="sndr_wp_mail_radio" type='radio' name='sndr_mail_method' value='wp_mail' <?php if ( $sndr_options['sndr_method'] == 'wp_mail' ) echo 'checked="checked"'; ?>/> 
									<?php _e( 'Wp-mail', 'sender' ); ?> <span class="sndr_info">(<?php _e( 'You can use the wp_mail function for mailing', 'sender' ); ?>)</span>
								</label><br/>
								<label>
									<input id="sndr_php_mail_radio" type='radio' name='sndr_mail_method' value='mail' <?php if ( $sndr_options['sndr_method'] == 'mail' ) echo 'checked="checked"'; ?>/> 
									<?php _e( 'Mail', 'sender' ); ?> <span class="sndr_info">(<?php _e( 'To send mail you can use the php mail function', 'sender' ); ?>)</span>
								</label><br/>
								<label>
									<input id="sndr_smtp_mail_radio" type='radio' name='sndr_mail_method' value='smtp' <?php if ( $sndr_options['sndr_method'] == 'smtp' ) echo 'checked="checked"'; ?>/> 
									<?php _e( 'SMTP', 'sender' ); ?> <span class="sndr_info">(<?php _e( 'You can use SMTP for sending mails', 'sender' ); ?>)</span>
								</label>
							</td>
						</tr>
						<tr class="ad_opt sndr_smtp_options">
							<th><?php _e( 'SMTP Settings', 'sender' ); ?></td>
							<td></td>
						</tr>
						<tr class="ad_opt sndr_smtp_options">
							<th><?php _e( 'SMTP server', 'sender' ); ?></th>
							<td><input type='text' name='sndr_mail_smtp_host' value='<?php echo $sndr_options['sndr_smtp_settings']['host']; ?>' /></td>
						</tr>
						<tr class="ad_opt sndr_smtp_options">
							<th><?php _e( 'SMTP port', 'sender' ); ?></th>
							<td><input type='text' name='sndr_mail_smtp_port' value='<?php echo $sndr_options['sndr_smtp_settings']['port']; ?>' /></td>
						</tr>
						<tr class="ad_opt sndr_smtp_options">
							<th><?php _e( 'SMTP account', 'sender' ); ?></th>
							<td><input type='text' name='sndr_mail_smtp_accaunt' value='<?php echo $sndr_options['sndr_smtp_settings']['accaunt']; ?>' /></td>
						</tr>
						<tr class="ad_opt sndr_smtp_options">
							<th><?php _e( 'SMTP password', 'sender' ); ?></th>
							<td><input type='password' name='sndr_mail_smtp_password' value='<?php echo $sndr_options['sndr_smtp_settings']['password']; ?>' /></td>
						</tr>
						<tr class="ad_opt sndr_smtp_options">
							<th><?php _e( 'Use SMTP SSL', 'sender' ); ?></th>
							<td><input type='checkbox' name='sndr_ssl' <?php if ( $sndr_options['sndr_smtp_settings']['ssl'] ) echo 'checked="checked"'; ?>/></td>
						</tr>
					</table>
					<?php if ( false == sndr_check_subscriber_install() ) {
						echo '<p>' . __( 'If you want to allows your site visitors to subscribe for newsletters, coming from your website, use', 'sender' ) . ' <a href="http://wordpress.org/plugins/subscriber/">Subscriber plugin</a> ' . __( 'that is an exclusive add-on for the Sender Plugin by BestWebSoft.', 'sender' ) . '</p>';
					} ?>
					<p class="submit">
						<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Save Changes', 'sender' ) ?>" />
						<input type="hidden" name="sndr_form_submit" value="submit" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sndr_nonce_name' ); ?>
					</p>				
				</form>
				<div class="bws-plugin-reviews">
					<div class="bws-plugin-reviews-rate">
						<?php _e( 'If you enjoy our plugin, please give it 5 stars on WordPress', 'sender' ); ?>: 
						<a href="http://wordpress.org/support/view/plugin-reviews/sender" target="_blank" title="Sender reviews"><?php _e( 'Rate the plugin', 'sender' ); ?></a>
					</div>
					<div class="bws-plugin-reviews-support">
						<?php _e( 'If there is something wrong about it, please contact us', 'sender' ); ?>: 
						<a href="http://support.bestwebsoft.com">http://support.bestwebsoft.com</a>
					</div>
				</div>
			<?php } elseif ( 'go_pro' == $_GET['action'] ) { ?>
				<?php if ( isset( $pro_plugin_is_activated ) && true === $pro_plugin_is_activated ) { ?>
					<script type="text/javascript">
						window.setTimeout( function() {
						    window.location.href = 'admin.php?page=sndrpr_settings';
						}, 5000 );
					</script>				
					<p><?php _e( "Congratulations! The PRO version of the plugin is successfully download and activated.", 'sender' ); ?></p>
					<p>
						<?php _e( "Please, go to", 'sender' ); ?> <a href="admin.php?page=sndrpr_settings"><?php _e( 'the setting page', 'sender' ); ?></a> 
						(<?php _e( "You will be redirected automatically in 5 seconds.", 'sender' ); ?>)
					</p>
				<?php } else { ?>
					<form method="post" action="admin.php?page=sndr_settings&amp;action=go_pro">
						<p>
							<?php _e( 'You can download and activate', 'sender' ); ?> 
							<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro">PRO</a> 
							<?php _e( 'version of this plugin by entering Your license key.', 'sender' ); ?><br />
							<span style="color: #888888;font-size: 10px;">
								<?php _e( 'You can find your license key on your personal page Client area, by clicking on the link', 'sender' ); ?> 
								<a href="http://bestwebsoft.com/wp-login.php">http://bestwebsoft.com/wp-login.php</a> 
								<?php _e( '(your username is the email you specify when purchasing the product).', 'sender' ); ?>
							</span>
						</p>
						<?php if ( isset( $bstwbsftwppdtplgns_options['go_pro']['sender-pro/sender-pro.php']['count'] ) &&
							'5' < $bstwbsftwppdtplgns_options['go_pro']['sender-pro/sender-pro.php']['count'] &&
							$bstwbsftwppdtplgns_options['go_pro']['sender-pro/sender-pro.php']['time'] < ( time() + ( 24 * 60 * 60 ) ) ) { ?>
							<p>
								<input disabled="disabled" type="text" name="bws_license_key" value="<?php echo $bws_license_key; ?>" />
								<input disabled="disabled" type="submit" class="button-primary" value="<?php _e( 'Activate', 'sender' ); ?>" />
							</p>
							<p>
								<?php _e( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'sender' ); ?>
							</p>
						<?php } else { ?>
							<p>
								<input type="text" name="bws_license_key" value="<?php echo $bws_license_key; ?>" />
								<input type="hidden" name="bws_license_plugin" value="sender-pro/sender-pro.php" />
								<input type="hidden" name="bws_license_submit" value="submit" />
								<input type="submit" class="button-primary" value="<?php _e( 'Activate', 'sender' ); ?>" />
								<?php wp_nonce_field( plugin_basename(__FILE__), 'bws_license_nonce_name' ); ?>
							</p>
						<?php } ?>
					</form>				
				<?php }
			} ?>
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
		$uesr_count_by_roles = 0;
		$roles               = array();
		$add_condition       = function_exists( 'sbscrbr_users_list' ) ? " AND `" . $wpdb->prefix . "sndr_mail_users_info`.`black_list`=0 AND `" . $wpdb->prefix . "sndr_mail_users_info`.`delete`=0 " : '';
		if ( is_multisite() ) {
			$users_roles_list = $wpdb->get_results(
				"SELECT `user_id`, `meta_value`,
					( SELECT COUNT(`id_user`) FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . " ) AS `all` 
				FROM `" . $wpdb->prefix . "usermeta` 
				LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info` ON `" . $wpdb->prefix . "usermeta`.`user_id`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user`
				WHERE `meta_key` LIKE '%capabilities%' AND `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . " ORDER BY `meta_value`;", 
				ARRAY_A 
			);
			$user_roles = $roles = array();
			$all_count  = $users_roles_list[0]['all']; /* all users count */
			foreach( $users_roles_list as $key => $role_data ) {
				$role = key( unserialize( $role_data['meta_value'] ) ); /* get name of role */
				if( ! array_key_exists( $role, $user_roles ) ) { /* if current role not added in $role */
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
		} else {
			$rol = $wpdb->get_results(
				"SELECT `meta_value`, COUNT(`meta_value`) AS `role_count`, 
					( SELECT COUNT(`id_user`) FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . ") AS `all` 
				FROM `" . $wpdb->prefix . "usermeta` 
					LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info` ON  `" . $wpdb->prefix . "usermeta`.`user_id`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user`
				WHERE `meta_key` = '" . $wpdb->prefix . "capabilities' AND `" . $wpdb->prefix . "sndr_mail_users_info`.`subscribe`=1" . $add_condition . " GROUP BY `meta_value`",
				ARRAY_A 
			);
			$roles = array();
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
		} /* deduce the mail form */ ?>
		<div class="sndr-mail wrap" id="sndr-mail">
			<div id="icon-options-general" class="icon32 icon32-bws"></div>
			<h2><?php echo $title; ?></h2>
			<?php $action_message = sndr_report_actions();
			if ( $action_message['error'] ) {
				$sndr_error = $action_message['error'];
			} elseif ( $action_message['done'] ) {
				$sndr_message = $action_message['done'];
			} ?>
			<div class="error" <?php if ( empty( $sndr_error ) ) { echo 'style="display:none"'; } ?>><p><strong><?php echo $sndr_error; ?></strong></div>
			<div class="updated" <?php if ( empty( $sndr_message ) ) echo 'style="display: none;"'?>><p><?php echo $sndr_message ?></p></div>
			<form method="post">
				<table id="sndr-mail-send-table" class="form-table">
					<tr>
						<th><?php _e( 'Send to', 'sender' ); ?></td>
						<td>
							<label class="sndr-user-roles">
								<input class='sndr-check-all' type="checkbox" name="sndr_send_all" value="1" <?php if ( isset( $_POST['sndr_send_all'] ) && '1' == $_POST['sndr_send_all'] ) { echo 'checked="checked"'; } ?>/> 
								<?php _e( 'all', 'sender' ); ?>	( <span class="sndr-count"><?php echo $all_count; ?></span> )
							</label>
							<?php foreach ( $roles as $role=>$value ) {
								if ( isset( $_POST['sndr_user_name'] ) && array_key_exists( $role, $_POST['sndr_user_name'] ) && '1' == $_POST['sndr_user_name'][ $role ]) { 
									$checked = 'checked="checked"';
									$uesr_count_by_roles += intval( $value );
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
							<span class="sndr_info"><?php _e( 'Number of mails which would be sent', 'sender' ); ?>: 
								<span id="sndr-calculate">
									<?php if ( isset( $_POST['sndr_send_all'] ) && '1' == $_POST['sndr_send_all'] ) { 
										echo $all_count; 
									} elseif ( ! empty( $uesr_count_by_roles ) ) {
										echo $uesr_count_by_roles;
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
 */	
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
			global $wpdb;
			$search                = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->found_data      = $this->report_list();
			$this->items           = $this->found_data;
			$per_page              = $this->get_items_per_page( 'reports_per_page', 30 );
			$current_page          = $this->get_pagenum();
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
		 * Function to add column of checboxes 
		 * @param int    $item->comment_ID The custom column's unique ID number.
		 * @return string                  with html-structure of <input type=['checkbox']>
		 */
		function column_cb( $item ) {
			return sprintf( '<input id="cb_%1s" type="checkbox" name="report_id[]" value="%2s" />', $item['id'], $item['id'] );
		}

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
		* Function to add filters below and above reports ist
		* @param array $which An array of report types. Accepts 'Done', ''In progress.
		* @return void 
		*/
		function extra_tablenav( $which ) {
			global $wpdb;
			$all_count     = $done_count = $in_progress_count = 0;
			$filters_count = $wpdb->get_results (
				"SELECT COUNT(`mail_send_id`) AS `all`,
					( SELECT COUNT(`mail_send_id`) FROM " . $wpdb->prefix . "sndr_mail_send WHERE `mail_status`=1 ) AS `done`,
					( SELECT COUNT(`mail_send_id`) FROM " . $wpdb->prefix . "sndr_mail_send WHERE `mail_status`=0 ) AS `in_progress`
				FROM " . $wpdb->prefix . "sndr_mail_send"
			); 
			foreach( $filters_count as $count ) {
				$all_count         = empty( $count->all ) ? 0 : $count->all;
				$done_count        = empty( $count->done ) ? 0 : $count->done;
				$in_progress_count = empty( $count->in_progress ) ? 0 : $count->in_progress;
			} ?>
			<ul class="subsubsub">
				<li><a class="sndr-filter<?php if ( ! isset( $_REQUEST['mail_status'] ) ) { echo " current"; } ?>" href="?page=view_mail_send"><?php _e( 'All', 'sender' ); ?><span class="sndr-count"> ( <?php echo $all_count; ?> )</span></a> | </li>
				<li><a class="sndr-filter<?php if( isset( $_REQUEST['mail_status'] ) && "in_progress" == $_REQUEST['mail_status'] ) { echo " current"; } ?>" href="?page=view_mail_send&mail_status=in_progress"><?php _e( 'In progress', 'sender' ); ?><span class="sndr-count"> ( <?php echo $in_progress_count; ?> )</span></a> | </li>
				<li><a class="sndr-filter<?php if( isset( $_REQUEST['mail_status'] ) && "done" == $_REQUEST['mail_status'] ) { echo " current"; } ?>" href="?page=view_mail_send&mail_status=done"><?php _e( 'Done', 'sender' ); ?><span class="sndr-count"> ( <?php echo $done_count; ?> )</span></a></li>
				<!-- li><a class="sndr-filter" href="#"><?php _e( 'Trash', 'sender' ); ?><span class="sndr-count">(  )</span></li -->
			</ul><!-- .subsubsub --> 
		<?php  }

		/**
		 * Function to add action links to drop down menu before and after reports list
		 * @return array of actions
		 */
		function get_bulk_actions() {
			$actions = array();
			$actions['delete_reports']  = __( 'Delete Reports', 'sender' );
			//$action['stop_mailouts']    = __( 'Stop Mailout', 'sender' );
			//$actions['trash_reports']   = __( 'Trash Reports', 'sender' );
			//$actions['untrash_reports'] = __( 'Restore Report', 'sender' );
			return $actions;
		}

		/**
		 * Fires when the default column output is displayed for a single row.
		 * @param string $column_name      The custom column's name.
		 * @param int    $item->comment_ID The custom column's unique ID number.
		 * @return void
		 */
		function column_default( $item, $column_name ) {
			switch( $column_name ) {
				case 'status':
				case 'date':
				case 'subject':
					return $item[ $column_name ];
				default:
					return print_r( $item, true ) ;
			}
		}

		/**
		 * Function to add action links to subject column depenting on status page
		 * @param int      $item->comment_ID The custom column's unique ID number.
		 * @return string                     with action links
		 */
		function column_subject( $item ) {
			$mail_status = isset( $_REQUEST['mail_status'] ) ? '&mail_status=' . $_REQUEST['mail_status'] : '';
			$actions = array();
			$actions['show_report'] = '<a class="sndr-show-users-list" href="' . wp_nonce_url( '?page=view_mail_send&action=show_report&report_id=' . $item['id'] . '&list_paged=0&list_per_page=30' . $mail_status, 'sndr_show_report' . $item['id'] ) . '">' . __( 'Show Report', 'sender' ) . '</a>';
			//$actions['stop_mailout']   = sprintf( '<a href="?page=view_mail_send&action=stop_mailout&report_id[]=%s">' . __( 'Stop Mailout', 'sender' ) . '</a>', $item['id'] );
			//$actions['trash_report']   = sprintf( '<a href="?page=view_mail_send&action=trash_report&report_id[]=%s">' . __( 'Trash Report', 'sender' ) . '</a>', $item['id'] );
			//$actions['untrash_report'] = sprintf( '<a href="?page=view_mail_send&action=untrash_report&report_id[]=%s">' . __( 'Restore Report', 'sender' ) . '</a>', $item['id'] );
			$actions['delete_report']  = '<a href="' . wp_nonce_url( '?page=view_mail_send&action=delete_report&report_id=' . $item['id'] . $mail_status, 'sndr_delete_report' . $item['id'] ) . '">' . __( 'Delete Report', 'sender' ) . '</a>';

			return sprintf( '%1$s %2$s', $item['subject'], $this->row_actions( $actions ) );
		}

		/**
		 * Function to add necessary class and id to table row
		 * @param array $report with report data 
		 * @return void
		 */
		function single_row( $report ) {
			if( preg_match( '/done-status/', $report['status'] ) ) {
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
			$done_status        = '<p class="sndr-done-status" title="' . __( 'All Done', 'sender' ) . '">' . __( 'done', 'sender' ) . '</p>';
			$in_progress_status = '<p class="sndr-inprogress-status" title="' . __( 'In Progress', 'sender' ) . '">' . __( 'In progress', 'sender' ) . '</p>';
			$reports_list       = array();  
			$per_page = intval( get_user_option( 'reports_per_page' ) );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = 30;
			}
			$start_row = ( isset( $_REQUEST['paged'] ) && '1' != $_REQUEST['paged'] ) ? $per_page * ( absint( $_REQUEST['paged'] - 1 ) ) : 0;
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
			$sql_query = "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send` ";
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
					$reports_list[$i]['status']  = '1' == $report['mail_status'] ? $done_status : $in_progress_status;
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
		 * Function to display status of report
		 * @param string $mail_id id of report 
		 * @return string         'done'- ,'inprogress' or 'unknown'- statuses
		 */
		public function show_status( $mail_id ) {
			global $wpdb;
			$total_count = $send_count = $status = null;
			$count_mail = $wpdb->get_results(
				"SELECT COUNT(`id_mail`) AS `total`, 
					( SELECT COUNT(`id_mail`) FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" .$mail_id . " AND `status`=1 ) AS `send`
				FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" .$mail_id
			);
			if ( ! empty( $count_mail ) ) {
				foreach ( $count_mail as $count ) {
					$total_count = $count->total;
					$send_count  = $count->send;
				}
				if ( $total_count == $send_count ) {
					$status = '<span class="sndr-done-status" title="' . __( 'All Done', 'sender' ) . '">' . __( 'done', 'sender' ) . '</span>';
				} else {
					$status = '<span class="sndr-inprogress-status" title="' . __( 'In Progress', 'sender' ) . '">' . $send_count .' / ' . $total_count . '</span>';
				}
			} else {
				$status = '<span class="sndr-unknown-status" title="' . __( 'Unknown Status', 'sender' ) . '">' . '?' . '</span>';
			}
			return $status;
		}

		/**
		 * Function to show list of subscribers
		 * @param string $mail_id id of report 
		 * @return string         list of subscribers in table format
		 */
		public function show_report( $mail_id ) {
			$list_table = null;
			if ( isset( $_REQUEST['action'] ) && 'show_report' == $_REQUEST['action'] && $mail_id == $_REQUEST['report_id'] && check_admin_referer( 'sndr_show_report' . $mail_id ) ) {
				global $wpdb;
				$pagination = '';
				$report     = $_REQUEST['report_id'];
				if ( isset( $_POST['set_list_per_page_top'] ) || isset( $_POST['set_list_per_page_bottom'] ) ) { //query from subscribers pagination blocks
					// check if user want change number of subscriber which will br dysplayed on page
					if ( $_POST['set_list_per_page_top'] != $_POST['list_per_page'] ) {
						$per_page = ( empty( $_POST['set_list_per_page_top'] ) || ( ! preg_match( '/^\d+$/', $_POST['set_list_per_page_top'] ) ) ) ? $_REQUEST['list_per_page'] : $_POST['set_list_per_page_top'];
						$paged    = 0;
					} elseif( $_POST['set_list_per_page_bottom'] != $_POST['list_per_page'] ) {
						$per_page = ( empty( $_POST['set_list_per_page_bottom'] ) || ( ! preg_match( '/^\d+$/', $_POST['set_list_per_page_bottom'] ) ) ) ? $_REQUEST['list_per_page'] : $_POST['set_list_per_page_bottom'];
						$paged    = 0;
					//cheeck if user want to change number of page in text field
					} elseif( $_POST['list_paged_top'] != $_POST['current_page'] ) {
						$per_page   = $_REQUEST['list_per_page'];
						// if entered value is empty or not only digital
						$list_paged = ( empty( $_POST['list_paged_top'] ) || ( ! preg_match( '/^\d+$/', $_POST['list_paged_top'] ) ) ) ? '1' : $_REQUEST['list_paged_top'];
						//if entered value bigger than last page number
						$list_paged = intval( $_REQUEST['max_page_number'] ) < intval( $list_paged ) ? $_REQUEST['max_page_number'] : $list_paged;
						$paged      = intval( $list_paged ) - 1;
					} elseif( $_POST['list_paged_bottom'] != $_POST['current_page'] ) {
						$per_page   = $_REQUEST['list_per_page'];
						// if entered value is empty or not only digital
						$list_paged = ( empty( $_POST['list_paged_bottom'] ) || ( ! preg_match( '/^\d+$/', $_POST['list_paged_bottom'] ) ) ) ? '1' : $_REQUEST['list_paged_bottom'];
						//if entered value bigger than last page number
						$list_paged = intval( $_REQUEST['max_page_number'] ) < intval( $list_paged ) ? $_REQUEST['max_page_number'] : $list_paged;
						$paged      = intval( $list_paged ) - 1;
					} else {
						$per_page   = $_REQUEST['list_per_page'];
						$paged      = $_POST['current_page'];
					}
				} else { //query from action link on "Subject" row
					$per_page = $_REQUEST['list_per_page'];
					$paged    = intval( $_GET['list_paged'] );
				}
				$list_order_by = isset( $_REQUEST['list_order_by'] ) ? $_REQUEST['list_order_by'] : 'user_display_name';
				if( isset( $_REQUEST['list_order'] ) ) {
					$list_order = 'ASC' == $_REQUEST['list_order'] ? 'DESC' : 'ASC';
					$link_list_order = $_REQUEST['list_order'];
				} else {
					$list_order = $link_list_order = 'ASC';
				}
				$mail_status = isset( $_REQUEST['mail_status'] ) ? '&mail_status=' . $_REQUEST['mail_status'] : '';
				$start_row   = $per_page * $paged;
				$users_list  = $wpdb->get_results( 
					"SELECT `status`, `view`, `try`, `user_display_name`, `user_email` 
					FROM `" . $wpdb->prefix . "sndr_users` 
					LEFT JOIN `" . $wpdb->prefix . "sndr_mail_users_info`  ON `" . $wpdb->prefix . "sndr_users`.`id_user`=`" . $wpdb->prefix . "sndr_mail_users_info`.`id_user`
					WHERE `id_mail`=" . $report . " ORDER BY " . $list_order_by . " " . $list_order . " LIMIT " . $per_page . " OFFSET " . $start_row . ";"
				);
				if ( ! empty( $users_list ) ) { 
					$list_table =
						'<table class="report">
							<thead>
								<tr scope="row">
									<td colspan="4">' . $this->subscribers_pagination( $report, $per_page, $paged, $list_order_by, $link_list_order, false, 'top' ) . '</td>
								</tr>
								<tr>
									<td class="sndr-username"><a href="?page=view_mail_send&action=show_report&report_id=' . $report . '&list_paged=' . $paged . '&list_per_page=' . $per_page . '&list_order_by=user_display_name&list_order=' . $list_order . $mail_status . '">' . __( 'Username', 'sender' ) . '</a></td>
									<td><a href="?page=view_mail_send&action=show_report&report_id=' . $report . '&list_paged=' . $paged . '&list_per_page=' . $per_page . '&list_order_by=status&list_order=' . $list_order . $mail_status . '">' . __( 'Status', 'sender' ) . '</a></td>
									<td style="display: none;"><a href="?page=view_mail_send&action=show_report&report_id=' . $report . '&list_paged=' . $paged . '&list_per_page=' . $per_page . '&list_order_by=view&list_order=' . $list_order . $mail_status . '">' . __( 'View', 'sender' ) . '</a></td>
									<td>' . __( 'Try', 'sender' ) . '</td>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td class="sndr-username"><a href="?page=view_mail_send&action=show_report&report_id=' . $report . '&list_paged=' . $paged . '&list_per_page=' . $per_page . '&list_order_by=user_display_name&list_order=' . $list_order . $mail_status . '">' . __( 'Username', 'sender' ) . '</a></td>
									<td><a href="?page=view_mail_send&action=show_report&report_id=' . $report . '&list_paged=' . $paged . '&list_per_page=' . $per_page . '&list_order_by=status&list_order=' . $list_order . $mail_status . '">' . __( 'Status', 'sender' ) . '</a></td>
									<td style="display: none;"><a href="?page=view_mail_send&action=show_report&report_id=' . $report . '&list_paged=' . $paged . '&list_per_page=' . $per_page . '&list_order_by=view&list_order=' . $list_order . $mail_status . '">' . __( 'View', 'sender' ) . '</a></td>
									<td>' . __( 'Try', 'sender' ) . '</td>
								</tr>
								<tr scope="row">
									<td colspan="4">' . $this->subscribers_pagination( $report, $per_page, $paged, $list_order_by, $link_list_order, true, 'bottom' ) . '</td>
								</tr>
							</tfoot>
							<tbody>';
					foreach( $users_list as $list ) {
						$user_name = empty( $list->user_display_name ) ? $list->user_email : $list->user_display_name;
						if ( empty( $user_name ) ) {
							$user_name = '<i>- ' . __( 'User was deleted', 'sender' ) . ' -</i>';
						}
						$list_table .= '<tr>
											<td class="sndr-username">' . $user_name . '</td>
											<td>';
						if( '1' == $list->status ) {
							$list_table .= '<p style="color: green;">' . __( 'received', 'sender' ) . '</p>';
						} else { 
							$list_table .= '<p style="color: #555;">' . __( 'in the queue', 'sender' ) . '</p>'; 
						}
						$list_table .=		'</td>
											<td style="display: none;">';
						if( '1' == $list->view ) {
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
						</table>';
				} else {
					//if( empty( $users_list ) )
					$list_table = '<p style="color:red;">' . __( "The list of subscribers can't be found.", 'sender' ) . '</p>';
				}
			}
			return $list_table;
		}

		/** 
		 * Function to get subscribers list pagination
		 * @param string  $mail_id        id of report
		 * @param string  $per_page       number of subscribers on each page
		 * @param string  $paged          desired page number
		 * @param string  $list_order_by  on what grounds will be sorting
		 * @param string  $list_order     "ASC" or "DESC
		 * @param bool    $show_hidden    show/not hidden fields 
		 * @param string  $place          postfix to fields name
		 * @return string                 pagination elements
		 */
		function subscribers_pagination( $mail_id, $per_page, $paged, $list_order_by, $list_order, $show_hidden, $place ) {
			global $wpdb;
			$users_count = $wpdb->get_var(
				"SELECT COUNT( `id_user` ) FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $mail_id . ";"
			);
			$mail_status = isset( $_REQUEST['mail_status'] ) ? '&mail_status=' . $_REQUEST['mail_status'] : '';
			// open block with pagination elements
			$pagination_block = 
				'<div class="sndr-pagination">
					<p class="total-users">' . __( 'Total Subscribers: ', 'sender' ) . $users_count . '</p>
					<div class="list-per-page">
						<input type="text" name="set_list_per_page_' . $place . '" value="' . $per_page . '" size="3" title="' . __( 'Number of Subscribers on Page', 'sender' ) . '"/>
						<span class="total_pages">' . __( 'on page', 'sender' ) . '</span>
					</div>';

			// if more than 1 page
			if ( intval( $users_count ) > $per_page ) {
				// get number of all pages
				$total_pages         = ceil( $users_count / $per_page ) - 1;
				$total_pages_display = $total_pages + 1;
				$current_page        = $paged + 1;
				// get size of <input type="text"/>
				if ( '9' < $total_pages && '99' >= $total_pages ) {
					$input_size = 2;
				} elseif ( '100' < $total_pages && '999' >= $total_pages ) {
					$input_size = 3;
				} elseif ( '1000' < $total_pages && '9999' >= $total_pages ) {
					$input_size = 4;
				} elseif ( '10000' < $total_pages && '99999' >= $total_pages ) {
					$input_size = 5;
				} else {
					$input_size = 1;
				}
				$pagination_block .= 
					'<div class="list-paged">';
				if ( 0 < $paged ) { // if this is NOT first page of subscribers list
					$previous_page_link = ( 1 < $paged ) ? $paged - 1 : 0;
					$pagination_block .= 
						'<a class="first-page" href="?page=view_mail_send&action=show_report&report_id=' . $mail_id . '&list_paged=0&list_per_page=' . $per_page . $mail_status . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order . '" title="' . __( 'Go to the First Page', 'sender' ) . '">&laquo;</a>
						<a class="previous-page" href="?page=view_mail_send&action=show_report&report_id=' . $mail_id . '&list_paged=' . $previous_page_link . '&list_per_page=' . $per_page . $mail_status . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order . '" title="' . __( 'Go to the Previous Page', 'sender' ) . '">&lsaquo;</a>';
				} else { // if this is first page of subscribers list
					$pagination_block .= 
						'<span class="first-page-disabled">&laquo;</span>
						<span class="previous-page-disabled">&lsaquo;</span>';
				}
				// field to choose number of subscribers on page and current page
				$pagination_block .= 
					'<input type="text" class="page-number" name="list_paged_' . $place . '" value="' . $current_page . '" size="' . $input_size . '" title="' . __( 'Current Page', 'sender' ) . '"/>
					<span class="total_pages">' . __( 'of ', 'sender' ) . $total_pages_display . __( ' pages', 'sender' ) . '</span>';
				
				if ( $show_hidden ) {
					$pagination_block .= 
						'<input type="hidden" name="action" value="show_report"/>
						<input type="hidden" name="report_id" value="' . $mail_id . '"/>
						<input type="hidden" name="list_per_page" value="' . $per_page . '"/>
						<input type="hidden" name="current_page" value="' . $current_page . '"/>
						<input type="hidden" name="list_order_by" value="' . $list_order_by . '"/>
						<input type="hidden" name="list_order" value="' . $list_order . '"/>
						<input type="hidden" name="max_page_number" value="' . $total_pages_display . '"/>';
				}
								
				if ( ! empty( $mail_status ) ) {
					$pagination_block .= '<input type="hidden" name="mail_status" value="' . $_REQUEST['mail_status'] . '"/>';
				}

				if ( $paged < $total_pages ) { //if this is NOT last page
					$next_page_link = ( ( $paged - 1 ) < $total_pages ) ? $paged + 1 : $total_pages;
					$pagination_block .= 
						'<a class="next-page" href="?page=view_mail_send&action=show_report&report_id=' . $mail_id . '&list_paged=' . $next_page_link . '&list_per_page=' . $per_page . $mail_status . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order . '" title="' . __( 'Go to the Next Page', 'sender' ) . '">&rsaquo;</a>
						<a class="last-page" href="?page=view_mail_send&action=show_report&report_id=' . $mail_id . '&list_paged=' . $total_pages . '&list_per_page=' . $per_page . $mail_status . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order . '" title="' . __( 'Go to the Last Page', 'sender' ) . '">&raquo;</a>';
				} else { //if this is last page
					$pagination_block .= 
						'<span class="next-page-disabled">&rsaquo;</span>
						<span class="last-page-disabled">&raquo;</span>';
				}
				$pagination_block .= '</div><!-- .list-paged -->';
			}
			//close block with pagination elememnts
			$pagination_block .= '</div><!-- .sndr-pagination -->';
			return $pagination_block;
		}
	}
}
// the end of the SNDR_Report_List class definition


/**
 * Add screen options and initialize instance of class SNDR_Report_List
 * @return void 
 */
if ( ! function_exists( 'sndr_screen_options' ) ) {
	function sndr_screen_options() {
		global $sndr_reports_list;
		$option = 'per_page';
		$args = array(
			'label'   => __( 'Reports per page', 'sender' ),
			'default' => 30,
			'option'  => 'reports_per_page'
		);
		add_screen_option( $option, $args );
		$sndr_reports_list = new SNDR_Report_List();
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
			<div id="icon-options-general" class="icon32 icon32-bws"></div>
			<h2><?php _e( 'Sender Reports', 'sender' ); ?></h2>
			<?php $action_message = sndr_report_actions();
			if ( $action_message['error'] ) {
				$sndr_error = $action_message['error'];
			} elseif ( $action_message['done'] ) {
				$sndr_message = $action_message['done'];
			} ?>
			<div class="error" <?php if ( empty( $sndr_error ) ) { echo 'style="display:none"'; } ?>><p><strong><?php echo $sndr_error; ?></strong></div>
			<div class="updated" <?php if ( empty( $sndr_message ) ) echo 'style="display: none;"'?>><p><?php echo $sndr_message ?></p></div>
			<?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) {
				printf( '<span class="subtitle">' . sprintf( __( 'Search results for &#8220;%s&#8221;', 'sender' ), wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) ) . '</span>' );
			} ?>
			<form method="post">
				<?php $sndr_reports_list->prepare_items();
				$sndr_reports_list->search_box( __( 'search', 'sender' ), 'sndr' );
				$bulk_actions = $sndr_reports_list->current_action();
				$sndr_reports_list->display(); 
				wp_nonce_field( plugin_basename( __FILE__ ), 'sndr_mail_view_nonce_name' ); ?>
			</form>
		</div><!-- .wrap .sndr-report-list-page -->
	<?php }
}

/**
 * Redirect to "report" page
 * @return void
 */
if ( ! function_exists( 'sndr_redirect' ) ) {
	function sndr_redirect() {
		if ( isset( $_REQUEST['page'] ) && 'sndr_send_user' == $_REQUEST['page'] 
			&& isset( $_REQUEST['sndr_content'] ) && ( ! empty( $_REQUEST['sndr_content'] ) )
			&& ( ( isset( $_POST['sndr_send_all'] ) && ( ! empty( $_POST['sndr_send_all'] ) ) ) || ( isset( $_POST['sndr_user_name'] ) && ( ! empty( $_POST['sndr_user_name'] ) ) ) )
			&& check_admin_referer( plugin_basename( __FILE__ ), 'sndr_create_mailout_nonce_name' ) ) {
			if ( is_multisite() ) {
				wp_redirect( network_admin_url( 'admin.php?page=view_mail_send&action=check_errors' ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=view_mail_send&action=check_errors' ) );
			}
		}
	}
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
								// delete all records about mail statistics
								$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $report );
								if ( $wpdb->last_error ) {
									$error ++;
								} else {
									$done ++;
								}
								// delete mail
								$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $report );
								if ( $wpdb->last_error ) { 
									$mail_error ++;
								} else {
									$mail_done ++;
								}
								// set message
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
									// delete all records about mail statistics
									$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_users` WHERE `id_mail`=" . $report );
									if ( $wpdb->last_error ) {
										$error ++;
									} else {
										$done ++;
									}
									// delete mail
									$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $report );
									if ( $wpdb->last_error ) { 
										$mail_error ++;
									} else {
										$mail_done ++;
									}
								}
								// set message
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
					case 'check_errors':
						break;
					case 'show_report':
					default:
						break;
				}
			/* add messages to database and registred cron */
			} elseif ( isset( $_POST['sndr_subject'] ) && isset( $_POST['sndr_content'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sndr_create_mailout_nonce_name' ) ) {
				if ( empty( $_POST['sndr_content'] ) ) { /* if empty content of mail */
					$action_message['error'] = $message_list['empty_content'];
				} else {
					$add_condition = function_exists( 'sbscrbr_users_list' ) ? " AND `black_list`=0 AND `delete`=0" : '';
					if ( isset( $_POST['sndr_send_all'] ) || isset( $_POST['sndr_user_name'] ) ) { // if not empty users list
						$blogusers_id = array();
						/* Save mail into database */
						$mail_data = array(
							'subject'		=> stripslashes( esc_html( $_POST['sndr_subject'] ) ), 
							'body'			=> $_POST['sndr_content'],
							'date_create'	=> time() 
						);
						require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
						if ( is_plugin_active( 'email-queue/email-queue.php' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
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
							$last_element = count($_POST['sndr_user_name'] );
							foreach(  $_POST['sndr_user_name'] as $key=>$value ) {
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
							}
						} else {
							$action_message['error'] = $message_list['cannot_get_users_list'];
						}
					} else { /* if empty users list */
						$action_message['error'] = $message_list['empty_users_list'];
					}
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
 * Function delete user.
 * @return void 
 */
if ( ! function_exists( 'sndr_mail_delete_user' ) ) {	
	function sndr_mail_delete_user( $user_id ) {
		global $wpdb;
		$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `id_user` ='" . $user_id . "';" );
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
		if ( function_exists( 'sbscrbr_users_list' ) ) { /* if Subscriber plugin already installed and activated */
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
		$current_user = get_userdata( $user_id );
		$user_exists  = $wpdb->query( "SELECT `id_user` FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `id_user`=" . $current_user->ID . ";" );
		if ( $user_exists ) {
			$subscriber   = ( isset( $_POST['sndr_mail_subscribe'] ) && '1' == $_POST['sndr_mail_subscribe'] ) ? '1' : '0';
			$wpdb->update( $wpdb->prefix . 'sndr_mail_users_info',
				array(
					'user_email'        => $current_user->user_email,
					'user_display_name' => $current_user->display_name,
					'subscribe'         => $subscriber
				),
				array(
					'id_user'           => $current_user->ID
				)
			);
		} else {
			if ( isset( $_POST['sndr_mail_subscribe'] ) && '1' == $_POST['sndr_mail_subscribe'] ) {
				$wpdb->insert( $wpdb->prefix . 'sndr_mail_users_info',
					array(
						'id_user'           => $current_user->ID,
						'user_email'        => $current_user->user_email,
						'user_display_name' => $current_user->display_name,
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
		global $wpmu;
		$sndr_options = ( 1 == $wpmu ) ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );
		$schedules['my_period'] = array( 'interval' => $sndr_options['sndr_run_time'] * 60, 'display' => __( 'Your interval', 'sender' ) );
		return $schedules;
	}
}

/**
 * Function to periodicaly mail sending
 * @return void
 */
if ( ! function_exists( 'sndr_cron_mail' ) ) {
	function sndr_cron_mail() {
		global $wp_version, $wpdb, $sndr_options, $wpmu;
		$sndr_options = ( 1 == $wpmu ) ? get_site_option( 'sndr_options' ) : get_option( 'sndr_options' );
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once( ABSPATH . "wp-includes/pluggable.php" ); 
		}
		if ( 'wp_mail' != $sndr_options['sndr_method'] ) {
			require_once( ABSPATH . WPINC . '/class-phpmailer.php' );
			$mail = new PHPMailer();
		}
		$sended		=	$errors	=	array();
		$from_name	=	'admin_name' == $sndr_options['sndr_select_from_field'] ? $sndr_options['sndr_from_admin_name'] : $sndr_options['sndr_from_custom_name'];
		$from_email	=	empty( $sndr_options['sndr_from_email'] ) ? get_option( 'admin_email' ) : $sndr_options['sndr_from_email'];

		$from_name	=	'=?UTF-8?B?' . base64_encode( $from_name ) . '?=';

		//get messages
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'email-queue/email-queue.php' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
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
				//get users
				$current_message		=	$users_mail_send['id_mail'];
				$mail_message			=	$wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id` = '" . $current_message . "' LIMIT 1;", ARRAY_A );
				$subject				=	'=?UTF-8?B?' . base64_encode( $mail_message['subject'] ) . '?=';
				$user_info				=	$wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_users_info` WHERE `id_user` = '" . $users_mail_send['id_user'] . "' LIMIT 1;", ARRAY_A );
				$mail_message['body']	=	apply_filters( 'sbscrbr_add_unsubscribe_link', $mail_message['body'], $user_info );
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
						
					} elseif ( 'smtp' == $sndr_options['sndr_method'] ) {
										
						$mail->IsSMTP();
						$mail->SMTPAuth = true;
						
						if ( $sndr_options['sndr_smtp_settings']['ssl'] ) {
							$mail->SMTPSecure = 'ssl';
						}
						
						$mail->Host = $sndr_options['sndr_smtp_settings']['host'];
						$mail->Port = $sndr_options['sndr_smtp_settings']['port']; 
						$mail->Username = $sndr_options['sndr_smtp_settings']['accaunt'];
						$mail->Password = html_entity_decode( $sndr_options['sndr_smtp_settings']['password'] );
						$mail->SetFrom( $from_email, $from_name );
						$mail->isHTML( true );
						$mail->Subject = $subject;
						$mail->MsgHTML( $body );
						$mail->AddAddress( $user_info['user_email'], $user_info['user_display_name'] );

						if ( $mail->Send() )
							$sended[] = $users_mail_send;
						else
							$errors[] = $users_mail_send;
						
						$mail->ClearAddresses();
						$mail->ClearAllRecipients();
					} elseif ( 'wp_mail' == $sndr_options['sndr_method'] ) {
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
				<div class="bws_table_bg"></div>
				<div class="wrap sndrpr-mail" id="sndrpr-mail" style="margin: 0 10px">
					<div id="icon-options-general" class="icon32 icon32-bws"></div>
					<h2><span><?php _e( 'Add new letter', 'sender' ); ?></span><a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h2>
					<table id="sndrpr-mail-send-table" class="sndrpr-page-table">
						<tr>
							<td>
								<label class="sndrpr-form-label"><?php _e( 'Subject:', 'sender' ); ?> 
								<input class="sndrpr-tltle" type="text" name="sndrpr_subject" value=""/></label>
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
												<p><input id="sndrpr-radio-1" class="sndrpr-default-radio" type="radio" value="1"  name="sndrpr_template_id" style="display: none;"/>One Column</p>
											</label>
										</li><!-- .sndrpr-template-info -->
										<li class="sndrpr-template-info">
											<label for="sndrpr-radio-2">
												<img src="<?php echo plugins_url( 'images/newsletter-icon-2.jpg', __FILE__ ); ?>" title="Two Column: Text Below the Image"/>
												<p><input id="sndrpr-radio-2" class="sndrpr-default-radio" type="radio" value="2"  name="sndrpr_template_id" style="display: none;"/>Two Column: Text Below the Image</p>
											</label>
										</li><!-- .sndrpr-template-info -->
										<li class="sndrpr-template-info">
											<label for="sndrpr-radio-3">
												<img src="<?php echo plugins_url( 'images/newsletter-icon-3.jpg', __FILE__ ); ?>" title="Two Column: Text Content Beside the Image"/>
												<p><input id="sndrpr-radio-3" class="sndrpr-default-radio" type="radio" value="3"  name="sndrpr_template_id" style="display: none;"/>Two Column: Text Content Beside the Image</p>
											</label>
										</li><!-- .sndrpr-template-info -->
									</ul><!-- .sndrpr-select-template -->
								</div><!-- .sndrpr-letter-custom -->
								<div class="sndrpr-letter-custom">
									<div class="sndrpr-help-box"></div><label class="sndrpr-form-label"><?php _e( 'Select additional fonts:', 'sender' ); ?></label>
									<ul class="sndrpr-fonts-list">
										<li><label><input name="sndrpr_font_id[]" value="1" type="checkbox"/><span style="font-family: 'Open Sans';">Open Sans</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="2" type="checkbox"/> <span style="font-family: 'Crimson Text';">Crimson Text</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="3" type="checkbox"/> <span style="font-family: 'Josefin Slab';">Josefin Slab</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="4" type="checkbox"/> <span style="font-family: 'Arvo';">Arvo</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="5" type="checkbox"/> <span style="font-family: 'Lato';">Lato</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="6" type="checkbox"/> <span style="font-family: 'Vollkorn';">Vollkorn</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="7" type="checkbox"/> <span style="font-family: 'Abril Fatface';">Abril Fatface</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="8" type="checkbox"/> <span style="font-family: 'Ubuntu';">Ubuntu</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="9" type="checkbox"/> <span style="font-family: 'PT Sans';">PT Sans</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="10" type="checkbox"/> <span style="font-family: 'Old Standard TT';">Old Standard TT</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="11" type="checkbox"/> <span style="font-family: 'Yanone Kaffeesatz';">Yanone Kaffeesatz</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="12" type="checkbox"/> <span style="font-family: 'Roboto';">Roboto</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="13" type="checkbox"/> <span style="font-family: 'Montserrat';">Montserrat</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="14" type="checkbox"/> <span style="font-family: 'Lusitana';">Lusitana</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="15" type="checkbox"/> <span style="font-family: 'Oswald';">Oswald</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="16" type="checkbox"/> <span style="font-family: 'Lora';">Lora</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="17" type="checkbox"/> <span style="font-family: 'Raleway';">Raleway</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="18" type="checkbox"/> <span style="font-family: 'Bitter';">Bitter</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="19" type="checkbox"/> <span style="font-family: 'Cabin';">Cabin</span></label></li>
										<li><label><input name="sndrpr_font_id[]" value="20" type="checkbox"/> <span style="font-family: 'Cuprum';">Cuprum</span></label></li>
									</ul><!-- .sndrpr-fonts-list -->
								</div><!-- .sndrpr-letter-custom -->
								<div class="clear"></div>
								<input type="submit" id="sndrpr-load-template-button"  name="sndrpr_additional_data" value="Load Additional Data"/>
								<input type="submit" id="sndrpr-reset-additional-data" name="sndrpr_reset_additional_data" value="Reset Additional Data"/>
							</td>
						</tr>
						<tr><td class="sndrpr-help-box"><div style="margin-top: 4px;"><strong><?php _e( 'Use shortcodes!', 'sender' ); ?></strong></div></td></tr>
						<tr>
							<td>
								<label class="sndrpr-form-label"><?php _e( 'Letter Content:', 'sender' ); ?></label>
								<div class="wp-core-ui wp-editor-wrap tmce-active"><link rel='stylesheet' id='editor-buttons-css'  href='#' type='text/css' media='all' />
									<div>
										<div class="wp-media-buttons"><a href="#" class="button insert-media add_media" title="Add Media"><span class="wp-media-buttons-icon"></span> <?php _e( 'Add Media' ); ?></a></div>
										<div class="wp-editor-tabs">
											<a class="wp-switch-editor switch-html" onclick="switchEditors.switchto(this);"><?php _e( 'Text' ); ?></a>
											<a class="wp-switch-editor switch-tmce" onclick="switchEditors.switchto(this);"><?php _e( 'Visual' ); ?></a>
										</div>
									</div>
									<div class="wp-editor-container">
										<textarea class="wp-editor-area" rows="30" autocomplete="off" cols="40" name="sndrpr_content" ></textarea>
									</div>
								</div>		
							</td>
						</tr>					
					</table>
					<p class="submit">
						<input type="button" class="button-small button" value="<?php _e( 'Create Mailout', 'sender' ); ?>"/>
						<input type="button" class="button-small button" value="<?php _e( 'Send Test Message', 'sender' ); ?>"/>
					</p>
					<p class="submit">
						<input type="submit" name="sndrpr_create_mailout" value="<?php _e( 'Create Mailout', 'sender' ); ?>" class="button-primary"/>
					</p>
				</div><!-- #sndrpr-mail .sndrpr-mail -->	
			</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'sender' ); ?> 
					<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>			
				</div>
				<div class="bws_pro_links">
					<a class="bws_button" href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>#purchase" target="_blank" title="Sender Pro Plugin">
						<?php _e( 'Go', 'sender' ); ?> <strong>PRO</strong>
					</a>
				</div>	
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
				<div class="bws_table_bg"></div>
					<div class="wrap sndr_letters_list" style="margin: 0 10px">
						<div id="icon-options-general" class="icon32 icon32-bws"></div>
						<h2><?php _e( 'Letters Lists', 'sender' ); ?> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h2>
						<ul class='subsubsub'>
							<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 3 )</span></a> |</li>
							<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
						</ul>
						<p class="search-box">
							<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
							<input type="search" name="s" value="" />
							<input type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  /></p>
							<div class="tablenav top">
								<div class="alignleft actions bulkactions">
									<select name='action'>
										<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
										<option value='trash_letters'><?php _e( 'Trash', 'sender' ); ?></option>
									</select>
									<input type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
								</div>
								<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
								<br class="clear" />
							</div>
							<table class="wp-list-table widefat fixed letters">
							<thead>
								<tr>
									<th scope='col'class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' class='manage-column column-subject sortable desc'>
										<a href="#"><span><?php _e( 'Subject', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-date sortable desc'>
										<a href="#"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' class='manage-column column-subject sortable desc'>
										<a href="#"><span><?php _e( 'Subject', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-date sortable desc'>
										<a href="#"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</tfoot>
							<tbody data-wp-lists='list:letter'>
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input type="checkbox" name="letter_id[]" value="3" />
									</th>
									<td class='subject column-subject'>
										<strong><a href="#">There is a new post</a></strong>
										<div class="row-actions">
											<span class='edit_letter'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_letter'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='date column-date'>2014-05-27 14:26:47</td>
								</tr>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="letter_id[]" value="3" />
									</th>
									<td class='subject column-subject'>
										<strong><a href="#">Get 30% Discount</a></strong>
										<div class="row-actions">
											<span class='edit_letter'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_letter'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='date column-date'>2014-05-27 14:26:47</td>
								</tr>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="letter_id[]" value="3" />
									</th>
									<td class='subject column-subject'>
										<strong><a href="#">Test letter</a></strong>
										<div class="row-actions">
											<span class='edit_letter'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_letter'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='date column-date'>2014-05-27 14:26:47</td>
								</tr>
						</table>
						<div class="tablenav bottom">
							<div class="alignleft actions bulkactions">
								<select name='action2'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_letters'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" id="doaction2" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
					</div><!-- .wrap -->
				</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'sender' ); ?> 
					<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>			
				</div>
				<div class="bws_pro_links">
					<a class="bws_button" href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>#purchase" target="_blank" title="Sender Pro Plugin">
						<?php _e( 'Go', 'sender' ); ?> <strong>PRO</strong>
					</a>
				</div>	
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
				<div class="bws_table_bg"></div>
					<div class="wrap sndr_distribution_list" style="margin: 0 10px">
						<div id="icon-options-general" class="icon32 icon32-bws"></div>
						<h2><?php _e( 'Mailings Lists', 'sender' ); ?> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h2>
						<ul class='subsubsub'>
							<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 3 )</span></a> |</li>
							<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
						</ul>
						<p class="search-box">
							<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
							<input type="search" name="s" value="" />
							<input type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  />
						</p>
						<div class="tablenav top">
							<div class="alignleft actions bulkactions">
								<select name='action'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_distributions'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
						<table class="wp-list-table widefat fixed mailings">
							<thead>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' class='manage-column column-name sortable desc'>
										<a href="№"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-date sortable desc'>
										<a href="№"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' class='manage-column column-name sortable desc'>
										<a href="№"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-date sortable desc'>
										<a href="№"><span><?php _e( 'Date of creation', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</tfoot>
							<tbody id="the-list" data-wp-lists='list:mailing'>
								<tr class="alternate">
									<th scope="row" class="check-column"><input type="checkbox" name="distribution_id[]" value=" 3" /></th>
									<td class='name column-name'>
										<strong><a href="#">Clients</a></strong> 
										<div class="row-actions">
											<span class='edit_distribution'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_distribution'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='date column-date'>2014-05-27 13:32:50</td>
								</tr>
								<tr>
									<th scope="row" class="check-column"><input type="checkbox" name="distribution_id[]" value=" 2" /></th>
									<td class='name column-name'>
										<strong><a href="#">Subscribers</a></strong> 
										<div class="row-actions">
											<span class='edit_distribution'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_distribution'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='date column-date'>2014-05-27 13:32:18</td>
								</tr>
								<tr class="alternate">
									<th scope="row" class="check-column"><input type="checkbox" name="distribution_id[]" value=" 1" /></th>
									<td class='name column-name'>
										<strong><a href="#">Administrators</a></strong> 
										<div class="row-actions">
											<span class='edit_distribution'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_distribution'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='date column-date'>2014-05-27 12:47:21</td>
								</tr>
							</tbody>
						</table>
						<div class="tablenav bottom">
							<div class="alignleft actions bulkactions">
								<select name='action2'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_distributions'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" id="doaction2" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">3 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
					</div><!-- .wrap  -->
				</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'sender' ); ?> 
					<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>			
				</div>
				<div class="bws_pro_links">
					<a class="bws_button" href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>#purchase" target="_blank" title="Sender Pro Plugin">
						<?php _e( 'Go', 'sender' ); ?> <strong>PRO</strong>
					</a>
				</div>	
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
				<div class="bws_table_bg"></div>
					<div class="wrap sndrpr-letter-template-page" style="margin: 0 10px">
						<div id="icon-options-general" class="icon32 icon32-bws"></div>
						<h2><?php _e( 'Letter Templates', 'sender' ); ?> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h2>
						<ul class='subsubsub'>
							<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 4 )</span></a> |</li>
							<li class='default'><a href="#"><?php _e( 'Default templates', 'sender' ); ?><span> ( 3 )</span></a> |</li>
							<li class='user'><a href="#"><?php _e( 'User`s templates', 'sender' ); ?><span> ( 1 )</span></a> |</li>
							<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
						</ul>
						<p class="search-box">
							<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
							<input type="search" name="s" value="" />
							<input type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  />
						</p>
						<div class="tablenav top">
							<div class="alignleft actions bulkactions">
								<select name='action'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_templates'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" id="doaction" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>" />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">4 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
						<table class="wp-list-table widefat fixed templates">
							<thead>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' id='title' class='manage-column column-title sortable desc'>
										<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' id='status' class='manage-column column-status sortable desc'>
										<a href="#"><span><?php _e( 'Status', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' id='title' class='manage-column column-title sortable desc'>
										<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' id='status' class='manage-column column-status sortable desc'>
										<a href="#"><span><?php _e( 'Status', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</tfoot>
							<tbody id="the-list" data-wp-lists='list:template'>
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input id="cb_1" type="checkbox" name="template_id[]" value=" 1" />
									</th>
									<td class='title column-title'>
										<strong><a href="#">One Column</a></strong> 
										<div class="row-actions">
											<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='status column-status'><i><?php _e( 'default', 'sender' ); ?></i></td>
								</tr>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="template_id[]" value=" 2" />
									</th>
									<td class='title column-title'>
										<strong><a href="#">Two Column: Text Below the Image</a></strong> 
										<div class="row-actions">
											<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='status column-status'><i><?php _e( 'default', 'sender' ); ?></i></td>
								</tr>
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input type="checkbox" name="template_id[]" value=" 3" />
									</th>
									<td class='title column-title'>
										<strong><a href="#">Two Column: Text Content Beside the Image</a></strong> 
										<div class="row-actions">
											<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='status column-status'><i><?php _e( 'default', 'sender' ); ?></i></td>
								</tr>
								<tr>
									<th scope="row" class="check-column"><input type="checkbox" name="template_id[]" value=" 4" /></th>
									<td class='title column-title'>
										<strong><a href="#">My Custom Template</a></strong> 
										<div class="row-actions">
											<span class='edit_template'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_template'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='status column-status'><i><?php _e( 'user`s', 'sender' ); ?></i></td>
								</tr>
							</tbody>
						</table>
						<div class="tablenav top">
							<div class="alignleft actions bulkactions">
								<select name='action'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_templates'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" id="doaction" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">4 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
					</div><!-- .wrap .sndrpr-letter-template-page -->
				</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'sender' ); ?> 
					<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>			
				</div>
				<div class="bws_pro_links">
					<a class="bws_button" href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>#purchase" target="_blank" title="Sender Pro Plugin">
						<?php _e( 'Go', 'sender' ); ?> <strong>PRO</strong>
					</a>
				</div>	
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
				<div class="bws_table_bg"></div>
					<div class="wrap sndrpr-priorities-list-page" style="margin: 0 10px">
						<div id="icon-options-general" class="icon32 icon32-bws"></div>
						<h2><?php _e( 'Priorities', 'sender' ); ?> <a href="#" class="add-new-h2"><?php _e( 'Add New', 'sender' ); ?></a></h2>
						<ul class='subsubsub'>
							<li class='all'><a class="current" href="#"><?php _e( 'All', 'sender' ); ?><span> ( 6 )</span></a> |</li>
							<li class='trash'><a href="#"><?php _e( 'Trash', 'sender' ); ?><span> ( 0 )</span></a></li>
						</ul>
						<p class="search-box">
							<label class="screen-reader-text"><?php _e( 'search:', 'sender' ); ?></label>
							<input type="search" name="s" value="" />
							<input type="submit" name="" class="button" value="<?php _e( 'search', 'sender' ); ?>"  />
						</p>
						<div class="tablenav top">
							<div class="alignleft actions bulkactions">
								<select name='action'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_priorities'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">6 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
						<table class="wp-list-table widefat fixed priorities">
							<thead>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' class='manage-column column-title sortable desc'>
										<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-number sortable desc'>
										<a href="#"><span><?php _e( 'Number', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th scope='col' class='manage-column column-cb check-column'>
										<label class="screen-reader-text"><?php _e( 'Select All', 'sender' ); ?></label>
										<input type="checkbox" />
									</th>
									<th scope='col' class='manage-column column-title sortable desc'>
										<a href="#"><span><?php _e( 'Title', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
									<th scope='col' class='manage-column column-number sortable desc'>
										<a href="#"><span><?php _e( 'Number', 'sender' ); ?></span><span class="sorting-indicator"></span></a>
									</th>
								</tr>
							</tfoot>
							<tbody id="the-list" data-wp-lists='list:priority'>
								<tr class="alternate">
									<th scope="row" class="check-column"><input type="checkbox" name="priority_id[]" value="6" /></th>
									<td class='title column-title'>
										<strong><a href="#">Other message</a></strong> 
										<div class="row-actions">
											<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='number column-number'>60</td>
								</tr>
								<tr>
									<th scope="row" class="check-column"><input type="checkbox" name="priority_id[]" value="5" /></th>
									<td class='title column-title'>
										<strong><a href="#">Service message</a></strong> 
										<div class="row-actions">
											<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='number column-number'>50</td>
								</tr>
								<tr class="alternate">
									<th scope="row" class="check-column"><input type="checkbox" name="priority_id[]" value="4" /></th>
									<td class='title column-title'>
										<strong><a href="#">Message of congratulations</a></strong> <div class="row-actions">
											<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='number column-number'>40</td>
								</tr>
								<tr>
									<th scope="row" class="check-column"><input type="checkbox" name="priority_id[]" value=" 3" /></th>
									<td class='title column-title'>
										<strong><a href="#">Advertising message</a></strong> <div class="row-actions">
											<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='number column-number'>30</td>
								</tr>
								<tr class="alternate">
									<th scope="row" class="check-column"><input type="checkbox" name="priority_id[]" value=" 2" /></th>
									<td class='title column-title'>
										<strong><a href="#">Special offer</a></strong> 
										<div class="row-actions">
											<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='number column-number'>20</td>
								</tr>
								<tr>
									<th scope="row" class="check-column"><input type="checkbox" name="priority_id[]" value=" 1" /></th>
									<td class='title column-title'>
										<strong><a href="#">Urgent message</a></strong> <div class="row-actions">
											<span class='edit_priority'><a href="#"><?php _e( 'Edit', 'sender' ); ?></a> | </span>
											<span class='trash_priority'><a href="#"><?php _e( 'Trash', 'sender' ); ?></a></span>
										</div>
									</td>
									<td class='number column-number'>10</td>
								</tr>
							</tbody>
						</table>
						<div class="tablenav bottom">
							<div class="alignleft actions bulkactions">
								<select name='action2'>
									<option value='-1' selected='selected'><?php _e( 'Bulk Actions', 'sender' ); ?></option>
									<option value='trash_priorities'><?php _e( 'Trash', 'sender' ); ?></option>
								</select>
								<input type="submit" name="" class="button action" value="<?php _e( 'Apply', 'sender' ); ?>"  />
							</div>
							<div class='tablenav-pages one-page'><span class="displaying-num">6 <?php _e( 'items', 'sender' ); ?></span></div>
							<br class="clear" />
						</div>
					</div><!-- .wrap .sndrpr-priorities-list-page -->
				</div>
			<div class="bws_pro_version_tooltip">
				<div class="bws_info">
					<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'sender' ); ?> 
					<a href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Sender Pro Plugin"><?php _e( 'Learn More', 'sender' ); ?></a>			
				</div>
				<div class="bws_pro_links">
					<a class="bws_button" href="http://bestwebsoft.com/plugin/sender-pro/?k=9436d142212184502ae7f7af7183d0eb&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>#purchase" target="_blank" title="Sender Pro Plugin">
						<?php _e( 'Go', 'sender' ); ?> <strong>PRO</strong>
					</a>
				</div>	
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
		if ( array_key_exists( 'subscriber/subscriber.php', $plugins_list ) ) {
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

		/* delete tables from database, users with role Mail Subscriber and role sbscrbr_subscriber( Mail Subscriber ) */
		if ( apply_filters( 'sndr_subscriber_installed', '' ) ) {
			$wpdb->query( "DROP TABLE `" . $wpdb->prefix . "sndr_mail_send`, `" . $wpdb->prefix . "sndr_users`" );
		} else {
			$wpdb->query( "DROP TABLE `" . $wpdb->prefix . "sndr_mail_send`, `" . $wpdb->prefix . "sndr_users`, `" . $wpdb->prefix . "sndr_mail_users_info`" );
		}

		/* delete plugin options */
		delete_site_option( 'sndr_options' );
		delete_option( 'sndr_options' );
	}
}

/**
 * Get admin email via AJAX
 * @return void
 */
if ( ! function_exists( 'sndr_get_admin_email' ) ) {
	function sndr_get_admin_email() {
		global $wpdb;
		if ( isset( $_POST['action'] ) && 'sndr_show_email' == $_POST['action'] ) {
			$admin_email = $wpdb->get_results( 
				"SELECT `user_email` FROM `" . $wpdb->prefix . "users` WHERE `display_name`='" . $_POST['display_name'] . "';", 
				ARRAY_A 
			);
			if ( ! empty( $admin_email ) ) {
				echo $admin_email[0]['user_email'];
				die();
			}
		}
	}
}

if ( ! function_exists ( 'sndr_plugin_banner' ) ) {
	function sndr_plugin_banner() {
		global $hook_suffix;	
		if ( 'plugins.php' == $hook_suffix ) { 
			global $bstwbsftwppdtplgns_cookie_add, $sndr_plugin_info;	  
			$banner_array = array(
				array( 'lmtttmpts_hide_banner_on_plugin_page', 'limit-attempts/limit-attempts.php', '1.0.2' ),
				array( 'sndr_hide_banner_on_plugin_page', 'sender/sender.php', '0.5' ),
				array( 'srrl_hide_banner_on_plugin_page', 'user-role/user-role.php', '1.4' ),
				array( 'pdtr_hide_banner_on_plugin_page', 'updater/updater.php', '1.12' ),
				array( 'cntctfrmtdb_hide_banner_on_plugin_page', 'contact-form-to-db/contact_form_to_db.php', '1.2' ),
				array( 'cntctfrmmlt_hide_banner_on_plugin_page', 'contact-form-multi/contact-form-multi.php', '1.0.7' ),		
				array( 'gglmps_hide_banner_on_plugin_page', 'bws-google-maps/bws-google-maps.php', '1.2' ),		
				array( 'fcbkbttn_hide_banner_on_plugin_page', 'facebook-button-plugin/facebook-button-plugin.php', '2.29' ),
				array( 'twttr_hide_banner_on_plugin_page', 'twitter-plugin/twitter.php', '2.34' ),
				array( 'pdfprnt_hide_banner_on_plugin_page', 'pdf-print/pdf-print.php', '1.7.1' ),
				array( 'gglplsn_hide_banner_on_plugin_page', 'google-one/google-plus-one.php', '1.1.4' ),
				array( 'gglstmp_hide_banner_on_plugin_page', 'google-sitemap-plugin/google-sitemap-plugin.php', '2.8.4' ),
				array( 'cntctfrmpr_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-pro/contact_form_pro.php', '1.14' ),
				array( 'cntctfrm_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.62' ),
				array( 'cntctfrm_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.47' ),	
				array( 'cptch_hide_banner_on_plugin_page', 'captcha/captcha.php', '3.8.4' ),
				array( 'gllr_hide_banner_on_plugin_page', 'gallery-plugin/gallery-plugin.php', '3.9.1' )				
			);
			if ( ! $sndr_plugin_info )
				$sndr_plugin_info = get_plugin_data( __FILE__ );	

			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			$active_plugins	=	get_option( 'active_plugins' );
			$all_plugins	=	get_plugins();
			$this_banner	=	'sndr_hide_banner_on_plugin_page';
			foreach ( $banner_array as $key => $value ) {
				if ( $this_banner == $value[0] ) {
					global $wp_version;
					if ( ! isset( $bstwbsftwppdtplgns_cookie_add ) ) {
						echo '<script type="text/javascript" src="' . plugins_url( 'js/c_o_o_k_i_e.js', __FILE__ ) . '"></script>';
						$bstwbsftwppdtplgns_cookie_add = true;
					} ?>
					<script type="text/javascript">		
							(function($) {
								$(document).ready( function() {		
									var hide_message = $.cookie( "sndr_hide_banner_on_plugin_page" );
									if ( hide_message == "true" ) {
										$( ".sndr_message" ).css( "display", "none" );
									} else {
										$( ".sndr_message" ).css( "display", "block" );
									}
									$( ".sndr_close_icon" ).click( function() {
										$( ".sndr_message" ).css( "display", "none" );
										$.cookie( "sndr_hide_banner_on_plugin_page", "true", { expires: 32 } );
									});	
								});
							})(jQuery);				
						</script>
					<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">					                      
						<div class="sndr_message bws_banner_on_plugin_page" style="display: none;">
							<img class="close_icon sndr_close_icon" title="" src="<?php echo plugins_url( 'images/close_banner.png', __FILE__ ); ?>" alt=""/>
							<div class="button_div">
								<a class="button" target="_blank" href="http://bestwebsoft.com/plugin/sender-pro/?k=c273031fe5f64b4ea95f2815ae9313b5&pn=114&v=<?php echo $sndr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>"><?php _e( 'Learn More', 'sender' ); ?></a>				
							</div>
							<div class="text"><?php
								_e( 'It’s time to upgrade your <strong>Sender plugin</strong> to <strong>PRO</strong> version!', 'sender' ); ?><br />
								<span><?php _e( 'Extend standard plugin functionality with new great options', 'sender' ); ?>.</span>
							</div> 		
							<div class="icon">			
								<img title="" src="<?php echo plugins_url( 'images/banner.png', __FILE__ ); ?>" alt=""/>	
							</div>
						</div>  
					</div>
					<?php break;
				}
				if ( isset( $all_plugins[ $value[1] ] ) && $all_plugins[ $value[1] ]["Version"] >= $value[2] && ( 0 < count( preg_grep( '/' . str_replace( '/', '\/', $value[1] ) . '/', $active_plugins ) ) || is_plugin_active_for_network( $value[1] ) ) && ! isset( $_COOKIE[ $value[0] ] ) ) {
					break;
				}
			}    
		}
	}
}

/**
 * Add all hooks
 */
register_activation_hook( plugin_basename( __FILE__ ), 'sndr_send_activate' );

if ( is_multisite() )
	add_action( 'network_admin_menu', 'sndr_admin_default_setup' );
else
	add_action( 'admin_menu', 'sndr_admin_default_setup' );

add_filter( 'plugin_action_links', 'sndr_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'sndr_register_plugin_links', 10, 2 );

add_action( 'profile_personal_options', 'sndr_mail_send' );
add_action( 'user_register', 'sndr_mail_register_user' );
//add_action( 'delete_user', 'sndr_mail_delete_user' );

add_action( 'admin_init', 'sndr_admin_init' );
add_action( 'admin_enqueue_scripts', 'sndr_admin_head' );
add_action( 'profile_update', 'sndr_update', 10, 2 );
add_filter( 'cron_schedules', 'sndr_more_reccurences' );
add_action( 'sndr_mail_hook', 'sndr_cron_mail' );
add_filter( 'set-screen-option', 'sndr_table_set_option', 10, 3 );
add_action( 'wp_ajax_sndr_show_email', 'sndr_get_admin_email' );
add_filter( 'sndr_subscriber_installed', 'sndr_check_subscriber_install' );

add_action( 'admin_notices', 'sndr_plugin_banner' );

add_action( 'mlq_change_status_on_sender_mail', 'sndr_get_update_on_mail_from_email_queue', 10, 3 );

register_deactivation_hook( plugin_basename( __FILE__ ), 'sndr_send_deactivate' );
register_uninstall_hook( plugin_basename( __FILE__ ), 'sndr_send_uninstall' );
?>