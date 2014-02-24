<?php
if ( isset( $_GET['get_mes'] ) ) {
	include( "../../../../wp-config.php" );
	$mes = $_GET['get_mes'];
	$secret = md5( 'bws' . $mes . 'mail_send' );
	if ( $_GET['s'] != $secret )
		exit();
	$link = mysql_connect( DB_HOST, DB_USER, DB_PASSWORD );
	mysql_select_db( DB_NAME, $link );
	mysql_query( "UPDATE `" . $wpdb->prefix . "sndr_mail__users` SET `view`=1 WHERE mail_users_id=" . $mes . ";");
	mysql_close( $link );
	$img = imageCreateTrueColor( 1, 1 );
	header( "Content-Type: image/png" );
	imagepng( $img );
	imagedestroy( $img );
}
?>