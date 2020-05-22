<pre>
<?php

$server = ( strpos($_SERVER['HTTP_HOST'],"prod") ? "prod" : "dev" );
if ( !( $nameSpace = @trim($_GET['inst']) ) || !stripos(",eawag,empa,psi,wsl,*,",$nameSpace) ) {
	$nameSpace = ( strchr("eawag,empa,psi,wsl",strtok(substr($_SERVER['REQUEST_URI'],1)."/","/")) ? strtok(substr($_SERVER['REQUEST_URI'],1)."/","/") : "*" );
}
$hostLabel = ( $nameSpace == "*" ) ? "Lib4RI site" : ( ( ( strlen($nameSpace) < 4 ) ? strtoupper($nameSpace) : ucfirst($nameSpace) ) . " sub-site" );
$instLabel = ( strlen($nameSpace) > 3 ) ? ucFirst($nameSpace) : strtoupper($nameSpace);

$ignLimit = @intval($_GET['limit']) ? intval($_GET['limit']) : 50;

$csvSep = ";";
$csvFile = "http://lib-dora-" . $server . "1.emp-eaw.ch:8080/solr/collection1/select";
$csvFile .= "?sort=PID+asc&rows=987654321&indent=true&wt=csv&csv.separator=" . urlencode($csvSep);
$csvFile .= "&q=PID%3A" . $nameSpace . "%5C%3A*+AND+dc.creator%3A*";
$csvFile .= "&fl=PID%2C+dc.creator%2C+mods_extension_originalAuthorList_mt";

$urlHere = "https://www.dora" . ( $server == "prod" ? "" : "-dev" ) . ".lib4ri.ch" .  strtok($_SERVER['REQUEST_URI']."?","?") . "?go";

$urlWiki = "https://www.wiki.lib4ri.ch/display/TD/1000+Authors+-+workflow";

$instLabel = ( strlen($nameSpace) > 3 ) ? ucFirst($nameSpace) : strtoupper($nameSpace);

if ( @intval($_GET['go']) < ( time() - 60 ) ) {
	if ( !user_is_logged_in() ) {
		echo "<br><i><b>You must be logged in to enjoy the full potential of this page!</b></i><br>\r\n";		// rather for safety
	} elseif ( @!isset($_GET['go']) || intval($_GET['go']) ) {
		echo "This page will look for publications with MODS datastreams that\r\n";
		echo "contain <a href='{$urlWiki}' target='_blank'>more than {$ignLimit} name elements for authors</a>.<br>\r\n";
		echo "- <a href=\"" . $urlHere . "&inst=eawag\"><u>Start this check for Eawag</u></a>\r\n";
		echo "- <a href=\"" . $urlHere . "&inst=empa\"><u>Start this check for Empa</u></a>\r\n";
		echo "- <a href=\"" . $urlHere . "&inst=psi\"><u>Start this check for PSI</u></a>\r\n";
		echo "- <a href=\"" . $urlHere . "&inst=wsl\"><u>Start this check for WSL</u></a>\r\n";
		echo "<br><b>Warning:</b> This check may <b>stress</b> the " . strtoupper($server) . " server for a several seconds!<br>\r\n";	
	} else {
		echo "Loading Solr data for {$instLabel} publications. This may take some seconds...\r\n";
		echo "<sc"."ript type=\"text/jav"."asc"."ript\"><!--\r\n";
		echo "var url = \"" . $urlHere . "=" . strval(time()) . "&inst={$nameSpace}\";\r\n";
		echo "setTimeout(\"window.location.href=url\",675);\r\n";
		echo "//--></script>\r\n";
	}
	return;
}


if ( !( $csvAry = @file($csvFile) ) ) {
	echo "ERROR: Could not ask Solr!?<br><br>";
	return;
}
$csvKeyAry = str_getcsv(array_shift($csvAry),$csvSep);		// kick the header row
$num_csv_keys = sizeof($csvKeyAry);

$valAry = array();
foreach( $csvAry as $row ) {
	$ary = str_getcsv($row,$csvSep);
	$ary = array_combine( $csvKeyAry, array_pad($ary,$num_csv_keys,'') );
	if ( @strpos($ary['PID'],':') ) { $valAry[] = $ary; }
}

$overAry = array();
foreach( $valAry as $autAry ) {
	$tmpAry = explode($csvSep,$autAry['dc.creator']);
	if ( sizeof($tmpAry) > $ignLimit ) {
		$ary = explode(':',$autAry['PID'],2);
		$idx = $ary[0] .':'. str_pad(strval($ary[1]),7,'0',STR_PAD_LEFT);
		$overAry[$idx] = $autAry;
	}
}
ksort($overAry);

// let's make a pretty report now:
$pNum = sizeof($overAry);
$html = ( $pNum ? strval($pNum) : '<i>no</i>' ) . ' publication' . ( $pNum == 1 ? '' : 's' );
$html = "<b>At {$instLabel} {$html} found with more than {$ignLimit} authors" . ( $pNum ? ':' : '!' ) . "</b><br>\r\n";
foreach( $overAry as $autAry ) {
	$pid = $autAry['PID'];
	$link = "https://www.dora" . ( $server == "prod" ? "" : "-dev" );
	$link .= ".lib4ri.ch/" . ( $nameSpace == '*' ? '' : $nameSpace.'/' ) . "islandora/object/{$pid}";
	$aNum = sizeof(explode($csvSep,$autAry['dc.creator']));
	$html .= "- <a href='{$link}' target='_blank'>{$pid}</a> &nbsp; (" . $aNum . " authors)<br>";
}
if ( $pNum ) {
	$html .= "<br><b>Please note</b>: A re-check of affiliations is not yet implemented, so this list\r\n";
	$html .= "may also contain publications with more than {$ignLimit} affiliated authors.\r\n";
}
echo $html . "<br>";


?></pre>
</pre>
