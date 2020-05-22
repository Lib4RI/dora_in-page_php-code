<?php

$server = ( @strpos($_SERVER['HTTP_HOST'],'prod1') ? 'lib-dora-prod1.emp-eaw.ch' : 'lib-dora-dev1.emp-eaw.ch' );
$csvUrl = "http://{$server}:8080/solr/collection1/select?sort=PID+asc&rows=987654321&fl=PID%2c+MADS_u1_mt%2c+MADS_family_mt%2c+MADS_note_mt&sort=PID+asc&rows=987654321&indent=true&wt=csv&csv.separator=%7c&q=PID:*-authors%5c%3a*+AND+MADS_note_mt:*";
$csvSep = "|";


if ( !function_exists('solr_csv_to_array_helper_pid7') ) {
	function solr_csv_to_array_helper_pid7($pid) {
		$idxAry = explode(':',$pid);
		return ( $idxAry[0] . ':' . str_pad($idxAry[1],7,'0',STR_PAD_LEFT) );
	}
}
	
if ( !function_exists('solr_csv_to_array') ) {
	function solr_csv_to_array($csvFile, $csvSep = ',', $indexField = '', $indexFunc = '', $getIdLess = true, $tuneUtf8 = true ) {
		if ( $csvSep == "" ) { return FALSE; }
		$csvAry = ( is_array($csvFile) ? $csvFile : NULL );
		if ( $csvAry === NULL && !( $csvAry = @file($csvFile) ) ) { return FALSE; }
		$valAry = array();
		while( !( $row = trim( array_shift($csvAry) ) ) ) { /* skip empty row */ continue; }
		$csvKeyAry = str_getcsv($row,$csvSep);
		if ( ( $num_csv_keys = sizeof($csvKeyAry) ) && ( $num_csv_keys == 1 || strpos($row,$csvSep) ) ) {	// safety checks
			$encAsUtf8 = false;
			if ( $tuneUtf8 ) {
				if ( strtoupper(urlencode(substr($csvKeyAry[0],0,3))) != '%EF%BB%BF' /* UFT8 header bytes */ ) {
					$csvKeyAry = array_map('utf8_encode',$csvKeyAry);
					$encAsUtf8 = true;
				} else { $csvKeyAry[0] = ltrim(substr($csvKeyAry[0],3)); }
			}
			$csvKeyAry = array_map('trim',$csvKeyAry);
			foreach( $csvAry as $row ) {
				if ( !empty(trim($row)) && ( $rAry = str_getcsv(trim($row),$csvSep) ) ) {
					$ary = ( sizeof($rAry) > $num_csv_keys ) ? array_slice($rAry,0,$num_csv_keys) : array_pad($rAry,$num_csv_keys,"");
					if ( $tuneUtf8 && $encAsUtf8 ) {
						$ary = array_map('utf8_encode',$ary);
					}
					if ( $rAry = array_combine($csvKeyAry,$ary) ) {
						$idxNow = ( empty($indexField) ? '' : @trim($rAry[$indexField]) );
						if ( !empty($indexFunc) && !empty($idxNow) ) {
							$ary = array($idxNow);
							if ( $ary = @array_map($indexFunc,$ary) ) { $idxNow = @array_shift($ary); }
						}
						if ( empty($idxNow) ) {
							$valAry[] = $rAry;
						} elseif ( @empty(trim($rAry[$indexField])) ) {
							if ( $getIdLess ) { $valAry['_no_id_'][] = $rAry; /* rather for rechecks, to AVOID with a given main ID */ }
						} elseif ( @!isset($valAry[$idxNow]) ) {
							$valAry[$idxNow] = $rAry;		// as intended
						} else {
							$valAry['_2x_id_'][] = $rAry;	// ...although it never should happen that $idx exists more than once!
						}
					}
				}
			}
		}
		return $valAry;
	}
}

$csvAry = solr_csv_to_array($csvUrl,$csvSep,'PID','solr_csv_to_array_helper_pid7');
ksort($csvAry);

$instNow = '';		// to state new institute
foreach( $csvAry as $autAry ) {
	if ( !( $pid = @$autAry['PID'] ) || !strpos($pid,':') ) { continue; }
	$inst = strtok(strtr($pid,'-',':'),':');
	if ( $instNow != $inst ) {
		echo ( empty($instNow) ? '<br>' : "</ul><hr>\r\n" );
		echo "<b>" . ( strlen($inst) < 4 ? strtoupper($inst) : ucfirst($inst) ) . "</b><br>\r\n";
		echo "<ul style='margin-top:0px; margin-bottom:20px'>\r\n";
		$instNow = $inst;
	}

	// $link = "https://www.dora.lib4ri.ch/{$inst}/islandora/object/{$pid}/datastream/MADS/view";
	// $link = "https://www.dora.lib4ri.ch/{$inst}/islandora/object/{$pid}/manage/datastreams/";
	// $link = "https://www.dora.lib4ri.ch/{$inst}/islandora/object/{$pid}/datastream/MADS/edit";
	$link = "https://www.dora.lib4ri.ch/{$inst}/islandora/edit_form/{$pid}/MADS";
	echo "<li><a target='_blank' href='" . $link . "'>{$pid}</a> : ";
	echo "<div style='display:inline'>" . utf8_decode($autAry['MADS_note_mt']) . "</div></li>\r\n";
}
echo "</ul>\r\n<br>\r\n";

?>

