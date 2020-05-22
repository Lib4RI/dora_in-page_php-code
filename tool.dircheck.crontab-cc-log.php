<!-- Drupal alias: crontab-log-citedby --><?php

$ipLong = ip2long( strtok($_SERVER['HTTP_X_FORWARDED_FOR'].",",",") );
$is_lib4ri_user = ( !user_is_anonymous() && ( ( $_SERVER['HTTP_HOST'] == "localhost" ) || 
		( /* Eawag+Empa */ $ipLong >= ip2long("152.88.0.0") && $ipLong <= ip2long("152.88.255.255") ) || 
			( /* WSL */ $ipLong >= ip2long("193.134.200.0") && $ipLong <= ip2long("193.134.207.255") ) || 
				( /* PSI */ $ipLong >= ip2long("192.33.118.0") && $ipLong <= ip2long("192.33.127.255") ) ) );

// Parsing Log for Links of Publications there we failed:
if ( $is_lib4ri_user && ( $_log = @$_GET['logPath'] ) && !stristr($_log,"http") && ( $dataAry = @file("/var/www/html/sites/".$_log) ) ) {
	$logNew = "<html><head><title>" . basename($_log) . "</title></head><body><pre>\t<b>" . basename($_log) . "</b><br>\r\n";
	$cut = "not available at Scopus:";
	$host = @$_SERVER['HTTP_HOST'];
	$host_web = "http://localhost";
	if ( !empty($host) && !stristr($host,"localhost") ) {
		$host_web = "https://www.dora.lib4ri.ch";
		if ( stristr($host,"dev") ) { $host_web = str_replace(".dora.",".dora-dev.",$host_web); }
	}
	foreach( $dataAry as $row ) {
		if ( $pos = strpos($row,$cut) ) {
			$pidAry = explode(",",substr($row,strlen($cut)+$pos));
			$logNew .= substr($row,0,$pos) . "<b>" . substr($row,$pos,strlen($cut)) . "</b>";
			$row = "";
			foreach( $pidAry as $idx => $pid ) {
				$pid = trim(rawurldecode($pid));
				if ( !empty($pid) && ( $inst = strtok($pid,":") ) ) {
					// https://www.dora.lib4ri.ch/eawag/islandora/object/eawag%3A14327/
					if ( ($idx % 10) == 0 ) { $row .= "\r\n\t"; }
					$row .= "<a href='{$host_web}/{$inst}/islandora/object/{$pid}' target='_blank'>{$pid}</a>" . str_pad("",6-strlen(strchr($pid,":"))) . ", ";
				}
			}
			$logNew .= substr($row,0,-2) . "\r\n";
		}
	}
	die( $logNew . "\r\n</pre></body></html>" );
	exit;
}

echo "<b><h4>Non-public auxiliary page to list <b>log files</b> created by the weekly Crontab job to build or update 'Cited-By' counts for Lib4RI publications.</h4></b>\r\n";
echo "<!--break--><!--- CSV Export File Deletion --><pre>\r\n";

date_default_timezone_set('CET');
$timeNow = time();
$siteAry = array();
if ( $is_lib4ri_user ) { $siteAry = array( "default" /* , "Eawag", "Empa", "PSI", "WSL" */ ); }

// echo "Explanation/example how to run a selective deletion:<br>Appending <i>?refTime=1544447777</i> onto the current page link would instantly delete all csv_export files older than this timestamp.</br>";
// echo "The current timestamp is " . $timeNow . ", for yesterday it is " . strval( $timeNow - 86400 ) . " (-24h), or&nbsp;simply select any pleasant timestamp from the list below.<br>\r\n\r\n";
echo "This page/node may reside on the Eawag sub-site only, however it will list the log files for all Lib4RI institutes.<br>";
echo "Nonetheless the servers <a href='https://www.dora-dev.lib4ri.ch/eawag/crontab-log-citedby'><b>Dev1</b></a> and <a href='https://www.dora.lib4ri.ch/eawag/crontab-log-citedby'><b>Prod1</b></a> are handled separately.</br>\r\n";

$lifeTime = ( @!isset($_GET['lifeTime']) ) ? -1 : (intval($_GET['lifeTime']) * 3600);
$refTime = ( @!isset($_GET['refTime']) ) ? 0 : intval($_GET['refTime']);

foreach( $siteAry as $site )
{
	$url = ( ( $site == "default" ) ? "sites/default" : strtolower("{$site}/sites/{$site}") );
	$url = "http://" . $_SERVER['HTTP_HOST'] . "/{$url}/files/";

	/*
	$logDir = @empty($_GET['logDir']) ? ( "/var/www/html/sites/" . strtolower($site) . "/files/" ) : str_replace("//","/",("/".rawurldecode($_GET['logDir'])."/"));
	$logName = @empty($_GET['logName']) ? "cit-data-update-cron.2" : rawurldecode($_GET['logName']);
	$logLen = strlen($logName);
	*/
	$logDir = "/var/www/html/sites/" . strtolower($site) . "/files/";
	$logName = "cit-data-update-cron.2";
	$logLen = 22;

	$scanAry = scandir($logDir, 1);

//	echo "<b>" . ( ( $site == "default" ) ? "Lib4RI main Site" : ucFirst($site) ) . "</b>:<br>\r\n";

	foreach( $scanAry as $item ) {
		if ( is_file($logDir.$item) && substr($item,0,$logLen) == $logName && substr($item,-4) == ".log" )
		{
			$timeFile = filemtime($logDir.$item);
			$timeStamp = $timeFile;				// = intval( substr($item,11,-4) );
			$style = "";
			if ( $lifeTime >= 0 ) {
				if ( ( $timeNow - $timeStamp ) >= $lifeTime ) {
					unlink($logDir.$item);
					$style = "text-decoration: line-through";
				}
			}
			elseif ( $refTime > 0 ) {
				if ( $timeStamp <= $refTime ) {
					unlink($logDir.$item);
					$style = "text-decoration: line-through";
				}
			}
			elseif ( $refTime < 0 ) {
				if ( $timeStamp >= ( 0 - $refTime ) ) {
					unlink($logDir.$item);
					$style = "text-decoration: line-through";
				}
			}
	//		$t_d = ( $timeFile - $timeStamp );
			echo "&nbsp; &nbsp; <a style='{$style}' href=\"" . $url . $item . "\">" . str_pad($item,48) . "</a> ";
	//		echo " &nbsp;  <i>" . $timeStamp . " " . ( ( $t_d == 0 ) ? "&plusmn;" : ( ( $t_d > 0 ) ? "<b>+" : "<b>" ) ) . $t_d . ( ( $t_d == 0 ) ? "" : "</b>" ) . "</i>";
			echo " <a href='?logPath={$site}/files/{$item}' target='_blank'>N/As</a> &nbsp; &nbsp; &nbsp; " . $timeFile . "</i>";
			echo " &nbsp;  <i class='perm'>" . @str_pad(substr(sprintf('%o',fileperms($logDir.$item)),-4),4,"0",STR_PAD_LEFT);
			echo " &nbsp; " . @str_pad( strval( filesize($logDir.$item) ),10," ",STR_PAD_LEFT) . " &nbsp; &nbsp; " . @date("Y-m-d H:i:s",$timeFile) . "</i>\r\n";
		}
	}
	echo "<br>\r\n";
}

if ( !$is_lib4ri_user ) { echo "<br><i>Sorry, you are not logged in or not coming from inside the Lib4RI network!</i><br>"; }
?></pre>
