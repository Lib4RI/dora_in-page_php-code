<b><h4>Non-public auxiliary page to list or delete Lib4RI Solr results export files on main site and all sub-sites.</h4></b>

<!--break--><!--- CSV Export File Listing/Deletion --><!-- Drupal alias: csv_export_files --><pre><?php

$ipLong = ip2long( strtok($_SERVER['HTTP_X_FORWARDED_FOR'].",",",") );
$is_lib4ri_user = ( !user_is_anonymous() && ( ( $_SERVER['HTTP_HOST'] == "localhost" ) || 
		( /* Eawag+Empa */ $ipLong >= ip2long("152.88.0.0") && $ipLong <= ip2long("152.88.255.255") ) || 
			( /* WSL */ $ipLong >= ip2long("193.134.200.0") && $ipLong <= ip2long("193.134.207.255") ) || 
				( /* PSI */ $ipLong >= ip2long("192.33.118.0") && $ipLong <= ip2long("192.33.127.255") ) ) );

date_default_timezone_set('CET');
$timeNow = time();
$dirAry = array( "default", "Eawag", "Empa", "PSI", "WSL" );

echo "Explanation/example how to run a selective deletion:<br>Appending <i>?refTime=1544447777</i> onto the current page link would instantly delete all export files older than this timestamp.</br>";
echo "The current timestamp is " . $timeNow . ", for yesterday it is " . strval( $timeNow - 86400 ) . " (-24h), or&nbsp;simply select any pleasant timestamp from the list below.<br>\r\n\r\n";

$lifeTime = ( @!isset($_GET['lifeTime']) ) ? -1 : (intval($_GET['lifeTime']) * 3600);
$refTime = ( @!isset($_GET['refTime']) ) ? 0 : intval($_GET['refTime']);
$onlyExt = @strtolower($_GET['onlyExt']);
$delFile = @trim(rawurldecode($_GET['delFile']));

foreach( $dirAry as $site )
{
	$url = ( ( $site == "default" ) ? "sites/default" : strtolower("{$site}/sites/{$site}") );
	$url = "http://" . $_SERVER['HTTP_HOST'] . "/{$url}/files/";

	$dir = "/var/www/html/sites/" . strtolower($site) . "/files/";

	$scanAry = scandir($dir, 1);

	echo "<b>" . ( ( $site == "default" ) ? "Lib4RI main Site" : ucFirst($site) ) . "</b>:<br>\r\n";
	foreach( $scanAry as $item ) {
		if ( !is_file($dir.$item) ) { continue; }

		$fileExt = strtolower(substr($item,strrpos($item,"."),4));		// for example .csv or .xls
		if ( $fileExt != ".xls" && $fileExt != ".csv" ) { continue; }
		if ( ".".strtolower(substr($item,0,4)) != $fileExt."_" || substr($item,3,9) != "_export_1" ) { continue; }
		if ( !empty($onlyExt) && !strchr($onlyExt,substr($fileExt,1)) ) { continue; }

		$timeFile = filemtime($dir.$item);
		$timeStamp = intval( substr($item,11,-4) );		// = filemtime($dir.$item);
		$style = "";
		if ( $delFile == $item ) {
			$style = "text-decoration: line-through";
			unlink($dir.$item);
		} else if ( $lifeTime >= 0 ) {
			if ( ( $timeNow - $timeStamp ) >= $lifeTime ) {
				unlink($dir.$item);
				$style = "text-decoration: line-through";
			}
		} elseif ( $refTime > 0 ) {
			if ( $timeStamp <= $refTime ) {
				unlink($dir.$item);
				$style = "text-decoration: line-through";
			}
		} elseif ( $refTime < 0 ) {
			if ( $timeStamp >= ( 0 - $refTime ) ) {
				unlink($dir.$item);
				$style = "text-decoration: line-through";
			}
		}
		$t_d = ( $timeFile - $timeStamp );
		echo "&nbsp; &nbsp; <a style='{$style}' href=\"" . $url . $item . "\">" . str_pad($item,28," ") . "</a>";
		echo "  &nbsp;  <i>" . $timeFile . " " . ( ( $t_d == 0 ) ? "&plusmn;" : ( ( $t_d > 0 ) ? "<b>+" : "<b>" ) ) . $t_d . ( ( $t_d == 0 ) ? "" : "</b>" ) . "</i>";
		echo "  &nbsp;  <i class='perm'>" . @str_pad(substr(sprintf('%o',fileperms($dir.$item)),-4),4,"0",STR_PAD_LEFT);
		echo " &nbsp; " . @str_pad( ( empty($style) ? strval(filesize($dir.$item)) : " &nbsp; <b>deleted</b>" ),10," ",STR_PAD_LEFT) . " &nbsp; " . @date("Y-m-d H:i:s",$timeFile) . "</i>";
		echo ( empty($style) ? " &nbsp; <a href='?delFile=".rawurlencode($item)."' title='Delete this " . strtoupper(substr($fileExt,1)) . " file *instantly*.'><font size='-1' color='red'>&empty;</font></a>\r\n" : "\r\n" );
	}
	echo "<br>\r\n";
}

if ( !$is_lib4ri_user ) { echo "<br><i>Sorry, you are not logged in or not coming from inside the Lib4RI network!</i><br>"; }
?></pre>
