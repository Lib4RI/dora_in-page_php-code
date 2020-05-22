<pre>
<?php

$server_job = ( strchr($_SERVER['HTTP_HOST'],"-prod1.") ? "prod" : "dev" );
$name_space = ( strchr("eawag,empa,psi,wsl",strtok(substr($_SERVER['REQUEST_URI'],1)."/","/")) ? strtok(substr($_SERVER['REQUEST_URI'],1)."/","/") : "*" );
$host_label = ( $name_space == "*" ) ? "Lib4RI site" : ( ( ( strlen($name_space) < 4 ) ? strtoupper($name_space) : ucfirst($name_space) ) . " sub-site" );


$csvFile = "http://lib-dora-{$server_job}1.emp-eaw.ch:8080/solr/collection1/select?wt=csv&csv.separator=%7C&indent=true&rows=987654321&sort=PID%20asc";
$csvFile .= "&fl=PID,%20mods_identifier_doi_mt,%20mods_identifier_scopus_mt,%20mods_identifier_ut_mt,%20mods_identifier_local_mt,%20fedora_datastream_latest_MODS_CREATED_ms,%20mods_note_additional?information_mt";
$csvFile .= "&q=PID%3A{$name_space}%5c%3a*+AND+(mods_identifier_doi_mt:*+OR+mods_identifier_scopus_mt:*+OR+mods_identifier_ut_mt:*+OR+mods_identifier_local_mt:*+OR+mods_note_additional%5c%20information_mt:Paper%5c%20ID%5c%3a*)";

// PID,mods_identifier_doi_mt,mods_identifier_local_mt,fedora_datastream_latest_MODS_CREATED_ms
$csvSep = "|";


$urlHere = "https://www.dora" . ( $server_job == "dev" ? "-dev" : "" ) . ".lib4ri.ch" .  strtok($_SERVER['REQUEST_URI']."?","?") . "?go";

if ( @intval($_GET['go']) < ( time() - 60 ) ) {
	if ( !user_is_logged_in() ) {
		echo "<br><i><b>You must be logged in to enjoy the full potential of this page!</b></i><br><br>\r\n";		// rather for safety
	} elseif ( @!isset($_GET['go']) || intval($_GET['go']) ) {
		echo "This page will search all publications of the {$host_label} and" . ( $server_job == "prod" ? " " : "<br>" );
		echo "check them for dupblicates based on a comparision of available IDs.\r\n";
		echo "<br><b>Warning:</b> This task may <b>stress</b> the " . strtoupper($server_job) . " server ";
		echo ( $server_job == "prod" ? "<b>seriously for a few minutes</b> (even after the result is displayed)!" : "<b>for a minute</b>." ) . "\r\n";	
		echo "<b>If</b> you think this is acceptable then <a href=\"" . $urlHere . "\"><u>start this search+check</u></a> now.<br>\r\n";
	} else {
		echo "Loading Solr data for all publications. This may take some seconds...\r\n";
		echo "<sc"."ript type=\"text/jav"."asc"."ript\"><!--\r\n";
		echo "var url = \"" . $urlHere . "=" . strval(time()) . "\";\r\n";
		echo "setTimeout(\"window.location.href=url\",675);\r\n";
		echo "//--></script>\r\n";
	}
	return;
}


if ( @!empty($csvFile) ) {
	$csvAry = file($csvFile);

	$csvKeyAry = str_getcsv(array_shift($csvAry),$csvSep);		// kick the header row
	$num_csv_keys = sizeof($csvKeyAry);
	
	$valAry = array();
	foreach( $csvAry as $row ) {
		$ary = str_getcsv($row,$csvSep);
		$valAry[] = array_combine( $csvKeyAry, array_pad($ary,$num_csv_keys,"") );
	}

	$idAry = array( 
		'doi' => array( 'mods_identifier_doi_mt', 'DOI' ),
		'scp' => array( 'mods_identifier_scopus_mt', 'Scopus ID' ),
		'wos' => array( 'mods_identifier_ut_mt', 'WoS ID' ),
		'loc' => array( 'mods_identifier_local_mt', 'Local ID' ),
		'ppi' => array( 'mods_note_additional information_mt', 'Paper ID' ),
	);

	$chkAry = array();
	foreach( $valAry as $vAry ) {
		$pid = $vAry['PID'];
		foreach( $idAry as $vId => $fSolr ) {
			$val = trim($vAry[$fSolr[0]]);	// for example:		$doi = trim($vAry['mods_identifier_doi_mt']);
			if ( $vId == "ppi" && $fSolr[0] == 'mods_note_additional information_mt' ) {
	//			echo "PPID :: " . strchr($val,$fSolr[1].":") . "<br>\t" . $val . "<br>\t" . strtok(strtr(strchr($val,$fSolr[1].":"),";,\n","|||")."|","|") . "<br>";
				$val = rtrim(strtok(strtr(strchr($val,$fSolr[1].":"),";,\n","|||")."|","|"));
			}
			if ( empty($val) ) { continue; }
			$idx = str_pad($val,8,".",STR_PAD_LEFT);
			$chkAry[$vId][$idx][] = $pid;
	//		ksort($chkAry[$vId][$idx]);
		}
	}

	foreach( $idAry as $vId => $fSolr ) { ksort($chkAry[$vId]); }


	foreach( $idAry as $vId => $fSolr ) {
//		if ( $name_space != "psi" && ( $vId == "ppi" || $vId == "loc" ) ) { continue; }
		$vAry = $chkAry[$vId];
		echo "<b>Dupblicates on the {$host_label} considering the " . $fSolr[1] . ":</b>\r\n";
		$found = 0;
		foreach( $vAry as $idx => $ary ) {
			if ( $idx != "000aux000" && sizeof($ary) > 1 ) {
				$hAry = array();
				foreach( $ary as $i => $pid ) {
					$hAry[] = "<a href='https://www.dora" . ( $server_job == "dev" ? "-dev" : "" ) . ".lib4ri.ch/" . ( $name_space == "*" ? "" : $name_space."/" ) . "islandora/object/" . $pid . "' target='_blank'>" . $pid . "</a>";
				}
				if ( sizeof($hAry) > 1 ) { echo "  " . str_pad(ltrim($idx,"."),45," ") . " " . implode(" / ",$hAry) . "\r\n"; }
				$found++;
			}
		}
		if ( $found == 0 ) { echo "<i>None found!</i>\r\n"; }
		echo "</b>\r\n";
	}

	echo "Data based on <a href=\"" . $csvFile . "\" target=\"_blank\"><i>indexed</i> publications in DORA</a>.\r\n";
}


?>
</pre>

