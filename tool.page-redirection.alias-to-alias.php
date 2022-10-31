<?php

$alias = 'units';	// target alias to redirect to, set old name as current alias (of this Drupal page/node)

$url = url('/'.$alias,array('absolute' => true));
global $user;
if ( $user->name == 'Drupal7' || in_array('administrator',$user->roles) ) {
	echo '<br>For Non-Admins this page will establish an automatic redirection to: <br><a href="' . $url . '">' . $url . '</a><br><br>';
	echo 'Bots still knowing this old location will get a 301 header message <br>that this page "Moved Permanently" now.<br><br>';
}
elseif ( @!empty($_SERVER['REQUEST_URI']) && !strpos($_SERVER['REQUEST_URI'],'/'.$alias) ) {
	drupal_add_http_header( 'Status', '301 Moved Permanently' );
	drupal_add_http_header( 'Location', $url );
	exit;
}
?>
