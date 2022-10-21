<b><h4>Non-public auxiliary page to list or delete Bot Logs created by the 'Citation Counts' block of the 'Detailed Record' web page.</h4></b>

<!--break--><!--- Bot log File Listing/Deletion --><!--- Drupal alias: web-citcount-bot-log --><pre style="font-size:larger;"><?php

$ipLong = ip2long( strtok($_SERVER['HTTP_X_FORWARDED_FOR'].",",",") );
$is_lib4ri_user = ( user_is_logged_in() && ( ( $_SERVER['HTTP_HOST'] == "localhost" ) || 
		( /* Eawag+Empa */ $ipLong >= ip2long("152.88.0.0") && $ipLong <= ip2long("152.88.255.255") ) || 
			( /* WSL */ $ipLong >= ip2long("193.134.200.0") && $ipLong <= ip2long("193.134.207.255") ) || 
				( /* PSI */ $ipLong >= ip2long("192.33.118.0") && $ipLong <= ip2long("192.33.127.255") ) ) );

date_default_timezone_set('CET');
$timeNow = time();
$dirAry = array();
if ( $is_lib4ri_user ) { $dirAry = array("Eawag", "Empa", "PSI", "WSL", "default" ); }

$isProd = ( stripos($_SERVER['HTTP_HOST'],'prod') !== false );
echo "Server <a href='https://www.dora-dev.lib4ri.ch/web-citedby-bot-log'><b>Dev1</b></a> should not be vistied by bots at all, so no (real) log files " . ( $isProd ? 't' : '' ) . "here. ";
echo ( $isProd ? 'Here on' : 'On' ) . " <a href='https://www.dora.lib4ri.ch/web-citedby-bot-log'><b>Prod1</b></a> however there should be log files listed, one per public sub-site.\r\n";

$lifeTime = ( @!isset($_GET['lifeTime']) ) ? -1 : (intval($_GET['lifeTime']) * 3600);
$refTime = ( @!isset($_GET['refTime']) ) ? 0 : intval($_GET['refTime']);


if ( @!empty($_GET['sortLog']) && ( $logAry = file( "/var/www/html/sites/".strtolower(strtok(rawurldecode($_GET['sortLog']),":"))."/files/".substr(strchr(rawurldecode($_GET['sortLog']),":"),1) ) ) ) {
	$uaAry = array();
	$dateStart = "";
	foreach( $logAry as $row ) {
		if ( $ua = strchr($row,"U/A:") ) {
			if ( @empty($dateStart) ) { $dateStart = strtok($row," "); }
			$ua = trim(substr(strtok($ua,"|"),4));
			$idx = strtolower($ua);
			$why = trim(strtr(substr(strtok(strchr($row,"Considered as Web bot"),"|"),23),"()","  "));
			$uaAry[$idx] = array( $ua, ( @$uaAry[$idx][1] + 1 ), ( $why == "no u/a, no lng" ? ( $ua == "''" ? "no User-Agent" : "no Language" ) : $why ) );
		}
	}

	$total = 0;
	ksort($uaAry);
	foreach ($uaAry as $key => $ary) {
		$bot[$key] = $ary[0];
		$count[$key] = $ary[1];
		$total += $ary[1];
	}
	array_multisort($count, SORT_DESC, $bot, SORT_ASC, $uaAry);
	
	echo "</pre>\r\n<pre style='font-size:larger; white-space:nowrap;'>Bot frequency on <b>" . strtok(rawurldecode($_GET['sortLog']),":") . "</b> since " . strtok($dateStart," ") . ":</b><br>\r\n";
	foreach( $uaAry as $ary ) {
		$d = round( 1000 * doubleval($ary[1]) / $total ) / 10.0;
		$p = ( doubleval(intval($d)) == $d ) ? "{$d}.0%" : "{$d}%";
		$html = str_pad(strval($ary[1])."x",7," ",STR_PAD_LEFT) . " " . str_pad("({$p})",9) . " " . str_pad(strval($ary[2]),16) . " " . $ary[0];
		echo str_replace(" ","&nbsp;",$html) . "<br>\r\n";
	}
	$dayCount = round( ( $timeNow - strtotime($dateStart) ) / ( 60 * 60 * 12 ) ) / 2.0;
	echo "Blocked access {$total}x within {$dayCount} days for assumed Bots (with totally " . sizeof($uaAry) . " different user agents).<br></pre>\r\n<pre>";

	unset( $dirAry );		// just to stop here
}

/*
if ( sizeof($dirAry) ) {
	echo "How to delete these logs: Appending <i>?refTime=1544447777</i> onto the current page link would instantly delete all log files older than this timestamp.</br>";
	echo "The current timestamp is " . $timeNow . ", for yesterday it is " . strval( $timeNow - 86400 ) . " (-24h), or&nbsp;simply select any pleasant timestamp from the list below.";
}
*/
echo "<br>\r\n";

foreach( $dirAry as $site )
{
	$url = ( ( $site == "default" ) ? "sites/default" : strtolower("{$site}/sites/{$site}") );
	$url = "http://" . $_SERVER['HTTP_HOST'] . "/{$url}/files/";

	$logDir = "/var/www/html/sites/" . strtolower($site) . "/files/";

	$scanAry = scandir($logDir, 1);

	echo "<b>" . ( ( $site == "default" ) ? "Lib4RI main Site" : ucFirst($site) ) . "</b>:\r\n";
	foreach( $scanAry as $item ) {
		if ( is_file($logDir.$item) && /* substr($item,0,12) == "cit-count.bot.log" && substr($item,-4) == ".csv" */ $item == "cit-count.bot.log" )
		{
			$timeFile = filemtime($logDir.$item);
			$timeStamp = $timeFile;				// = intval( substr($item,11,-4) );
			$style = "";
			/*
			if ( $lifeTime >= 0 ) {
				if ( ( $timeNow - $timeStamp ) >= $lifeTime ) {
					unlink($logDir.$item);
					$style = "text-decoration: line-through";
				}
			} elseif ( $refTime > 0 ) {
				if ( $timeStamp <= $refTime ) {
					unlink($logDir.$item);
					$style = "text-decoration: line-through";
				}
			} elseif ( $refTime < 0 ) {
				if ( $timeStamp >= ( 0 - $refTime ) ) {
					unlink($logDir.$item);
					$style = "text-decoration: line-through";
				}
			}
			*/
			echo "&nbsp; <a href='?sortLog=".$site.":".rawurlencode($item)."'>&raquo;<b>Sort Bots</b>&laquo;</a>";
			echo " in <a href=\"" . $url . $item . "\">" . $item . "</a> (";
			echo strval((filesize($logDir.$item)+512)>>10) ."kB, " . date("Y-m-d H:i:s",$timeFile);
		//	echo ", " . substr(sprintf('%o',fileperms($logDir.$item)),-4);
			echo ")";
		}
	}
	echo "<br>\r\n";
}

if ( !$is_lib4ri_user ) { echo "<br><i>Sorry, you are not logged in or not coming from inside the Lib4RI network!</i><br>"; }
?></pre>
