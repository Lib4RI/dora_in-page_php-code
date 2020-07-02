<?php
echo "<br>This is a test page intended to find <b>potential duplicates of author objects in DORA</b> by compating names.<br>";
echo "Assumed cases of duplicates are not shown if all of the object PIDs are sequential and if all MADS were created before 2020.<br>";
echo "Currently data for this page can be refreshed all 2 minutes.<br>";
echo "<br>Further tuning of this page was hold according to the <a href='https://www.wiki.lib4ri.ch/display/TD/2020-07-01' target='_blank'>Jour Fixe</a>.<br>";
echo "<br>To do:<br>";
echo "-  Intregrate finding duplicates by SAP-ID<br>";
echo "-  Intregrate finding duplicates by e-mail address<br>";
echo "- Do not show 'name duplicates' if all the SAP-IDs are different<br>";
echo "- Add an 'interception page' not to start the search instantly<br>";
echo "- Allow specifying an author name to look for duplicates<br>";
echo "- Allow limiting the time frame within to look for duplactes<br>";
echo "\r\n<pre>";

$hoursBack = -1;	// = 24 * 7;

$showSequential = @isset($_GET['all']);			// when URL ?all is set show also pot. dup. case with PID just increasing by 1.

$instAry = array(
	'eawag' => 'Eawag',
	'empa' => 'Empa',
	'psi' => 'PSI',
	'wsl' => 'WSL',
);


// -------------------------------------------------------------------------------

if ( !function_exists('autMadsDup_getLink') ) {
	function autMadsDup_getLink( $pid, $isDev ) {
		$inst = strtok(strtr($pid,'-',':'),':');
	//	return "https://www.dora" . ( $isDev ? '-dev' : '' ) . ".lib4ri.ch/{$inst}/islandora/object/{$pid}/datastream/MADS/view";
		return "https://www.dora.lib4ri.ch/{$inst}/islandora/object/{$pid}/manage/datastreams/";
	}
}
if ( !function_exists('autMadsDup_tagLink') ) {
	function autMadsDup_tagLink( $pid, $style = '', $server = '') {
		return "<a" . ( empty($style) ? '' : " style='{$style}'" ) . " target='_blank' href='" . autMadsDup_getLink($pid,strcmp($server,'prod')) . "'>{$pid}</a>";
	}
}

if ( !function_exists('autMadsDup_letter1x') ) {
	function autMadsDup_letter1x( $term, $ignCase = true ) {
		$tNew = '';
		for($c=0;$c<strlen($term);$c++) {
			if ( !$ignCase ) {
				if ( substr($tNew,-1,1) != substr($term,$c,1) ) { $tNew .= substr($term,$c,1); }
			}
			elseif ( stripos(substr($tNew,-1,1),substr($term,$c,1)) !== 0 ) { $tNew .= substr($term,$c,1); }			
		}
		return $tNew;
	}
}

if ( !function_exists('autMadsDup_simplifyTerm') ) {
	function autMadsDup_simplifyTerm( $term, $tune = 0, $gap = '-' ) {		// expecting an utf8_DEcoded term !
		if( $tune ) {
			$repAry = array();
			$repAry['è'] = 'e';
			$repAry['é'] = 'e';
			$repAry['ê'] = 'e';
			$repAry['à'] = 'a';
			$repAry['á'] = 'a';
			$repAry['ą'] = 'a';
			$repAry['ã'] = 'a';
			$repAry['ǎ'] = 'a';
			$repAry['å'] = 'a';
			$repAry['ô'] = 'o';
			$repAry['ò'] = 'o';
			$repAry['ó'] = 'o';
			$repAry['ø'] = 'o';
			$repAry['ż'] = 'z';
			$repAry['ï'] = 'i';
			$repAry['ì'] = 'i';
			$repAry['í'] = 'i';
			$repAry['î'] = 'i';
			$repAry['ñ'] = 'n';
			$repAry['ł'] = 'l';
			$repAry['ç'] = 'c';
			$repAry['ć'] = 'c';
			$repAry['č'] = 'c';
			$repAry['š'] = 's';
			$repAry['ğ'] = 'g';
			$repAry['ř'] = 'r';
			$tmpAry = $repAry;
			foreach( $tmpAry as $rep => $orig ) { $repAry[strtoupper($rep)] = strtoupper($orig); }
			if( $tune < 2 ) {
				$repAry['ü'] = 'u';
				$repAry['ö'] = 'o';
				$repAry['ä'] = 'a';
				$repAry['Ö'] = 'O';
				$repAry['Ü'] = 'U';
				$repAry['Ä'] = 'A';
			} else {
				$repAry['ü'] = 'ue';
				$repAry['ö'] = 'oe';
				$repAry['ä'] = 'ae';
				$repAry['Ö'] = 'Oe';
				$repAry['Ü'] = 'Ue';
				$repAry['Ä'] = 'Ae';
				if ( $tune > 2 ) {
					$repAry['Ph'] = 'F';
					$repAry['ph'] = 'f';
				}
			}
			$repAry["'"] = '';
			$repAry["´"] = '';
			$repAry["`"] = '';
			$repAry["’"] = '';
			$repAry["?"] = '';
			$repAry["."] = ' ';
			foreach( $repAry as $old => $new ) { $term = str_replace($old,$new,$term); }
		}
		$term = str_replace(' ',$gap,trim($term,".'- \t\n\r\0\x0B"));
		while( ( $pos = strpos($term,$gap.$gap) ) !== false ) { $term = substr($term,0,$pos) . substr($term,$pos+1); }
		$term = strtolower($term);
	//	$term = preg_replace('/\&[0-9a-z]+;/','!',htmlentities($term));		// for any other special letter let's set an !
		return $term;
	}
}

if ( !function_exists('lib4ri_author_update_csv_to_array') ) {
	function lib4ri_author_update_csv_to_array($csvFile, $csvSep = ',', $indexField = '', $indexFunc = '', $getIdLess = true, $tuneUtf8 = true ) {
		
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
					$rAry = ( sizeof($rAry) > $num_csv_keys ) ? array_slice($rAry,0,$num_csv_keys) : array_pad($rAry,$num_csv_keys,"");
					if ( $tuneUtf8 && $encAsUtf8 ) {
						$rAry = array_map('utf8_encode',$rAry);
					}
					if ( $rAry = array_combine($csvKeyAry,$rAry) ) {
						if ( empty($indexField) ) {
							$valAry[] = $rAry;
							continue;
						}
						$idx = @trim($rAry[$indexField]);
						if ( !empty($indexFunc) && ( $ary = @array_map($indexFunc,array($idx)) ) ) { 
							$idx = array_shift($ary);
						}
						if ( empty($idx) ) {
							if ( $getIdLess ) { $valAry['_no_id_'][] = $rAry; /* rather for rechecks */ }
						} elseif ( @isset($valAry[$idx]) ) {
							$valAry['_2x_id_'][$idx][] = $rAry;	/* to be avoided, rather for rechecks */
						} else {
							$valAry[$idx] = $rAry;		// as intended
						}
					}
				}
			}
		}
		if ( @isset($valAry['_2x_id_']) ) {		// copy upwards the 1st duplicate (but not moving it)
			foreach( $valAry['_2x_id_'] as $idx => $ary ) { array_unshift( $valAry['_2x_id_'][$idx], $valAry[$idx] ); }
		}			
		return $valAry;
	}
}

// -------------------------------------------------------------------------------


$server = ( @strpos($_SERVER['HTTP_HOST'],'dev') ? 'dev' : 'prod' );

$pubUrl = "http://lib-dora-{$server}1.emp-eaw.ch:8080/solr/collection1/select";
$pubUrl .= "?q=PID:[inst]%5c%3a*+AND+[solrField]:[pid]&fl=PID&sort=PID+asc&rows=987654321&wt=csv&indent=true&csv.separator=%7c";

$csvSep = '|';
$csvYear = ( ( $hoursBack >= 0 ) ? date( "Y", ( time() - ( 3600 * $hoursBack ) ) ) : '' );

$nameAutAry = array();

foreach( $instAry as $inst => $instName ) {

	$solrUrl = "http://lib-dora-{$server}1.emp-eaw.ch:8080/solr/collection1/select?q=(PID:{$inst}-authors%5c%3a*+NOT+PID:*%5c%3acollect*)+fedora_datastream_version_MADS_CREATED_mt:{$csvYear}*&sort=PID+asc&rows=987654321&fl=PID%2c+MADS_email_mt%2c+MADS_title_mt%2c+MADS_family_mt%2c+MADS_given_mt%2c+MADS_u1_mt%2c+MADS_organization_mt%2c+MADS_variant_family_mt%2c+MADS_variant_given_mt%2c+fedora_datastream_version_MADS_CREATED_mt&sort=PID+asc&rows=987654321&indent=true&wt=csv&csv.separator=" . $csvSep;

	$csvDoraBase = '/tmp/DORA-authors.' . $instAry[$inst] . '.Dup-Check.since-';		// also needed to unlink() !
	$csvDoraFile = $csvDoraBase . ( empty($csvYear) ? 'ever' : strval($csvYear) ) . date(".Y-m-d.H-i", ( ( time() >> 7 ) << 7 ) ) . '.csv';

	$csvDoraAry = array();
	if ( @filesize($csvDoraFile) ) {
		$csvDoraAry = file($csvDoraFile);
	} else {
		@unlink($csvDoraBase.'*');
		if ( $csvDoraAry = file($solrUrl) ) {
			file_put_contents($csvDoraFile,implode('',$csvDoraAry));
		}
	}
	$solrAutAry = lib4ri_author_update_csv_to_array( $csvDoraAry, $csvSep, 'PID' );
	
	$famOnlyAry = array();
	foreach( $solrAutAry as $aIdx => $autAry ) {

		$famOrig = utf8_decode($autAry['MADS_family_mt']);
		$tmpAry = array_map('trim',explode('-',trim(strtr($famOrig,' ','-'))));
		$famAry = array();
		foreach( $tmpAry as $fIdx => $famName ) {
			if ( $fIdx == 0 ) {
				if ( strcmp($famName,'von') === 0 ) { continue; }
				if ( strcmp($famName,'van') === 0 ) { continue; }
				if ( strtolower($famName) == 'de' ) { continue; }
				if ( strtolower($famName) == 'al' ) { continue; }
				if ( strtolower($famName) == 'le' ) { continue; }
				if ( strtolower($famName) == 'la' ) { continue; }
			}
			$auxName = strtolower(autMadsDup_letter1x(autMadsDup_simplifyTerm($famName,1)));
			$famAry[] = $auxName;
			$famOnlyAry[$auxName][] = $famOrig;
		}
		if ( sizeof($tmpAry) > 1 ) {
			$auxName = strtolower(implode('_',$tmpAry));
			$famOnlyAry[$auxName][] = $famOrig;
			array_unshift($famAry,$auxName);
		}

		$solrAutAry[$aIdx]['auxList'] = $famAry;
	}
	

	foreach( $solrAutAry as $autAry ) {
		
		$dateAry = explode($csvSep,$autAry['fedora_datastream_version_MADS_CREATED_mt']);
		$madsDate = strtotime(array_shift($dateAry));
		if ( $hoursBack >= 0 && ( $madsDate < ( time() - ( 3600 * $hoursBack ) ) ) ) { continue; } 
		
		$famAry = $autAry['auxList'];
		$givOrig = strtolower( utf8_decode($autAry['MADS_given_mt']) );
		$givName = ''; // = strtolower(trim(strtok(strtr($givOrig,' -','..').'.','.')));
		foreach( $famAry as $auxName ) {
			if ( @sizeof($famOnlyAry[$auxName]) < 2 ) {
				$auxName .= ',' . substr($givOrig,0,1);
			} else {
				if ( empty($givName) ) {
					$tmpAry = array_map('trim',explode('.',strtr($givOrig,' -','..')));
					$givAry = array();
					foreach( $tmpAry as $gIdx => $gName ) {
						if ( empty($gName) ) { continue; }
						$givAry[] = ( $gIdx ? substr($gName,0,1) : autMadsDup_letter1x(autMadsDup_simplifyTerm($gName,1)) );
					}
					$givName = implode('_',$givAry);
				}
				$auxName .= ',' . $givName;
			}
			$nameAutAry[$inst][$auxName][] = $autAry;
		}
	}
	
}

$caseAry = array();
$html = '';
foreach( $nameAutAry as $inst => $nameAry ) {
//	$echo = $instAry[$inst] . ":\r\n";
 	$html = '<hr><b>' . $instAry[$inst] . ":</b>\r\n";
	$caseNum = 0;
	foreach( $nameAry as $auxName => $dupAry ) {
		if ( sizeof($dupAry) < 2 ) { continue; }
		$idxAry = array();
		foreach( $dupAry as $autAry ) { 
			$idxAry[$autAry['PID']] = array( intval(substr(strchr($autAry['PID'],':'),1)), $autAry['fedora_datastream_version_MADS_CREATED_mt'] );
		}
		$pidNum = sizeof($idxAry);
		if ( $pidNum < 2 ) { continue; }
		if ( !$showSequential ) {
			$pidSum = 0;
			foreach( $idxAry as $pid => $vAry ) { $pidSum += $vAry[0]; }
			$pidChk = 0;
			foreach( $idxAry as $pid => $vAry ) {
				if ( strpos(strtok($vAry[1].$csvSep,$csvSep),'202') === 0 /* = newly added author MADS since >= 2020, show it always! */ ) { break; }
				if ( ( abs( $vAry[0] * $pidNum - $pidSum ) * 2 ) <= ( ( $pidNum - 1 ) * $pidNum ) ) { $pidChk++; }
			}
			if ( $pidChk >= $pidNum ) { continue; }
		}

		$idxCase = '_';
		foreach( $idxAry as $pid => $vAry ) { $idxCase .= $vAry[0] . '_'; }
		if ( @in_array($idxCase,$caseAry) ) { continue; }

		if ( $caseNum ) { $html .= "\t\t------------------------------------------\r\n"; }

		foreach( $dupAry as $dIdx => $autAry ) {
			$tmp = ' :  <b>' . utf8_decode($autAry['MADS_family_mt']) . ', ' . utf8_decode($autAry['MADS_given_mt']) . "</b>\r\n";
			$tmp .= "\t\t\t\tSAP-ID: " . str_pad(@strval($autAry['MADS_u1_mt']),8,' ');
			$tmp .= ( empty($autAry['MADS_email_mt']) ? '' : '    E-Mail: ' . $autAry['MADS_email_mt'] ) . "\r\n";
			if ( !empty($autAry['MADS_organization_mt']) ) { $tmp .= "\t\t\t\tUnit: " . $autAry['MADS_organization_mt'] . "\r\n"; }
			
			$urlTmp = ( $inst == 'psi' ? 'mods_name_personal_alternativeName_nameIdentifier_authorId_mt' : 'mods_name_personal_nameIdentifier_authorId_mt' );
			$urlTmp = str_replace('[inst]',$inst,str_replace('[solrField]',$urlTmp,$pubUrl));
			$urlTmp = str_replace('[pid]',str_replace(':','%5c%3a',$autAry['PID']),$urlTmp);
			$linkAry = array();
			if ( $bibAry = @file($urlTmp) ) {
				array_shift($bibAry);
				if ( sizeof($bibAry) ) {
					$bibAry = array_map('trim',$bibAry);
					foreach( $bibAry as $pidPub ) {
						if ( !empty($pidPub) ) {
							$link = "https://www.dora" . ( strcmp($server,'prod') ? '-dev1' : '' ) . ".lib4ri.ch/{$inst}/islandora/object/{$pidPub}/manage/datastreams/";
							$linkAry[] = "<a target='_blank' href='{$link}'>{$pidPub}</a>";
						}
					}
				}
			}
			if ( $linkSum = sizeof($linkAry) ) {
				foreach( $linkAry as $idx => $link ) {
					$mod = ( ($idx+1) % 7 );
					if ( $idx == 0 ) { $tmp .= "\t\t\t\tPublication" . ( $linkSum == 1 ? '' : 's' ) . ': '; }
					elseif ( $mod == 0 ) { $tmp .= "\t\t\t\t"; }
					$tmp .= $link . ( ($idx+1) < $linkSum ? ( $mod == 6 ? ",\r\n" : ', ' ) : "\r\n" );
				}
			}

//			$echo .= "\t" . $autAry['PID'] . $tmp;
 			$html .= "\t" . autMadsDup_tagLink($autAry['PID'],'width:27ex; white-space:nowrap; display:inline-block;',$server) . $tmp;
		}
//		$echo .= "\r\n";
		$caseAry[] = $idxCase;
		$caseNum++;
	}
	if ( empty($caseNum) ) {
//		$echo .= "\tnothing found\r\n";
 		$html .= "\t<i>nothing found</i>\r\n";
	}
//	echo $echo . "\r\n";
	echo $html . "\r\n";
}
// file_put_contents("/tmp/_dup-check.authors.DORA.html","<html></body><pre>" . $html."\r\n" . "</pre></body></html>");

echo '<hr></pre>';
?>
