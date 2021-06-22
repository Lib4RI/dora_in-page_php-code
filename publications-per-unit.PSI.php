<?php				/* Browse by Eawag/Empa/WSL Organizational Units	*/
// Restrictive access when producing a cache file via URL:
// if ( @intval($_GET['delay']) && !user_is_logged_in() && strval($_GET['pin']) != '7kPdjE4Li' ) { echo 'Hello!<br>You are not allowed to access this page this way!<br><br>'; return; }

// institute configuration 1/2,
$showOnlyDiv = array( 9000 => 'Logistics LOG' );	// where NOT to show the laboratories. only array-index matters.
$showOnlyCPT = true; // Special case: for Division PSI/1000 show only CPT laboratory (according to Lothar's phone call May 28)
$showBracket = ( @!isset($_GET['bracket']) || !empty($_GET['bracket']) );

$combiRemark = "The combined number of publications may be lower than the sum of the individual values, since a few publications may be (incorrectly) assigned to an old and a new laboratory name at the same time.";
$combiAsterik = '<sup style="font-size:1.1em; font-weight:500; position:relative; top:-0.2em; left:0.2em;">*</sup>';
$combiAsterik2 = '<sup style="font-size:1.1em; font-weight:500; position:relative; top:-0.1em;">*</sup>';  // bottom

$cacheLifeTime = 7200;		// seconds (how long to respect dumped file with cached data), should be some longer than (optional) (p)re-caching with Crontab:
// 15 */2 * * * wget -q -b -t 1 -U "Mozilla/5.0 (Lib4ri/Crontab)" "http://lib-dora-prod1.emp-eaw.ch/empa/department-publication?pin=7kPdjE4Li&delay=250" --delete-after -O "/tmp/empa.pub-per-unit.html"

$percentFormat = '<div style="font-size:0.95em; color:#888;">&nbsp;%&nbsp;</div>';		// 2021-05-05: (visually) same size as linked number, grey, no brackets

$cssCol = 'border-left: 1px solid #e2e1e0;';
$cssRow = 'border-bottom: 1px solid transparent;';
$cssRowTop = 'border-bottom: 1px solid #111;';

$colAll100 = ( @intval($_GET['all']) == 100 );		// set this TRUE to show '100.0%' for the column with the Sum/Total of publications


$_delay = @intval($_GET['delay']);	// Delay between Solr two queries, in milli-seconds

$unitOkAry = array(	/*	'abc-units:123' => 'Label of Unit',		*/ );	// assoc. array to hold ids and labels of accpeted units (as in facet-sidebar)
$resultAry = array( /*	'abc-units:123' => $pubQueryAry,		*/ );

$pubQueryAry = array(		/* this array will be dumed info a Json file, incl. updated 'count' values */
	'All Publications' => array(
		'Total' => array(
			'query' =>  /* '?f[]=mods_genre_ms:*' */ '?' ,
		/*	'count' => -1,	*/
		),
		'With full text' => array(
			'query' => '?f[]=-RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text',
		/*	'count' => -1,	*/
		),
		'Open Access' => array(
			'query' => '?f[]=RELS_EXT_fullText_literal_ms:Open%5c%20Access',
		/*	'count' => -1,	*/
		),
	),
	'Journal Articles' => array( /* JA since 2020 */
		'Total' => array(
			'query' => '?f[]=mods_genre_ms:Journal%5c%20Article&f[]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00%3a00%3a00Z%20TO%20NOW]',
		/*	'count' => -1,	*/
		),
		'Open Access' => array(
			'query' => '?f[]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00%3a00%3a00Z%20TO%20NOW]&f[]=mods_genre_ms%3a%22Journal%20Article%22&f[]=RELS_EXT_fullText_literal_ms:Open%5c%20Access',
		/*	'count' => -1,	*/
		),
		'With embargo' => array(
			'query' => '?f[]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00%3a00%3a00Z%20TO%20NOW]&f[]=mods_genre_ms%3a%22Journal%20Article%22&f[]=RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29',
		/*	'count' => -1,	*/
		),
		'Restricted' => array(
			'query' => '?f[]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00%3a00%3a00Z%20TO%20NOW]&f[]=mods_genre_ms%3a%22Journal%20Article%22&f[]=RELS_EXT_fullText_literal_ms:Restricted%20OR%20RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text',
		/*	'count' => -1,	*/
		),
	),
);

global $user;
global $base_path;
$_inst = trim($base_path,'/');
$_date = ( @intval($_GET['date']) > 2020 && strtotime($_GET['date']) ) ? trim(strip_tags($_GET['date']),'+/|;:,.') : '';	// e.g. ?date=2021-03-29

$userRoles = array_values($user->roles);
$vipRole = '';
foreach( array('administrator','repo manager','editor') as $roleOk ) {
	if ( in_array($roleOk,$userRoles) ) {
		$vipRole = $roleOk;
		$cacheLifeTime = 5;		// over-ride: accepting (almost) no caching duration for some roles
		break;
	}
}

$cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/sites/' . ( empty($_inst) ? 'default' : $_inst ) . '/files/dora-page.pub-per-unit/';
if ( @!is_dir($cacheDir) ) { mkdir($cacheDir); }
$cacheFile = $cacheDir . ( empty($_inst) ? 'default' : $_inst ) . '.dora-page-cache.pub-per-unit.' . ( intval($_date) ? $_date : date("Y-m-d") ) . '.json';

$cacheList = array();
foreach( scandir($cacheDir) as $dirItem ) {
	if ( substr($dirItem,-5) == '.json' ) {
		if ( $date = strtok(substr(strchr($dirItem,'.20'),1),'.') ) { // to cut out e.g. 2021-03-29
			$cacheList[$date] = $dirItem;
		}
	}
}
// cache file handling:
$cacheAge = empty($_date) ? ( @filesize($cacheFile) ? ( time() - filemtime($cacheFile) ) : time() ) : 1 /* pretend 1 sec with with date preset */;
$cacheAry = ( $cacheAge > $cacheLifeTime ) ? array() : json_decode( file_get_contents($cacheFile), TRUE );

// cache test:
// echo "<pre>File now: " . $cacheFile . "<br>" . print_r($cacheList,true) . "</pre>"; // return;


// There are units omitted to be shown, see lib4ridora/includes/block.inc or:
// https://www.dora.lib4ri.ch/eawag/admin/structure/block/manage/lib4ridora/lib4ridora_organizational_units/configure?destination=front
$unitOmitAry = explode(PHP_EOL, variable_get('lib4ridora_organization_block_results_to_omit', '') );  // see lib4ridora, $omitted_facets
$unitOmitAry = array_map( function($uTmp) { return trim(strtok(strtr($uTmp,'|#','//').'/','/')); }, $unitOmitAry );  // to allow appending '//' comments/unit-name onto each line/unit-id 


// Solr (web) query to get all units we have for this institute:
$unitSolrLink = 'http://' . trim(exec('hostname')) . '.emp-eaw.ch:8080/solr/collection1/select';
$unitSolrLink .= '?indent=true&rows=987654321&wt=csv&csv.separator=|&sort=PID+asc&rows=987654321';
$unitSolrLink .= '&q=PID:' . ( empty($_inst) ? '*' : $_inst ) . '-units%5c%3a*+NOT+PID%3A*collect*&fl=PID%2c+MADS_department_mt';


$labOkAry = array();
$secOkAry = array();
// Put Solr's (csv) response into an array/collection and filtering-out it 'on the fly' for units to be suppressed:
foreach ( @file($unitSolrLink) as $row ) {
	$rowAry = str_getcsv(rtrim($row),'|');
	if ( !empty($_inst) && strpos($rowAry[0],$_inst) !== 0 ) { continue; }
	if ( in_array($rowAry[0],$unitOmitAry) ) { continue; }
	$unitLabel = trim( @empty($rowAry[2]) ? $rowAry[1] : array_slice($rowAry,1,sizeof($rowAry)) );
	if ( empty($unitLabel) /* only seen on DEV */ ) { continue; }
	if ( substr($unitLabel,2,2) != '00' ) { continue; }
	if ( substr($unitLabel,1,3) != '000' ) {
		$labOkAry[$rowAry[0]] = $unitLabel;
	} else {
		$secOkAry[$rowAry[0]] = $unitLabel;
	}
	$unitOkAry[$rowAry[0]] = $unitLabel;
}
/*
asort($labOkAry);
asort($secOkAry);
$unitOkAry['mods_name_personal_affiliation_department_mt'] = $labOkAry;
$unitOkAry['mods_name_personal_affiliation_section_mt'] = $secOkAry;
*/
$unitOkAry = $labOkAry;
asort($unitOkAry);
/*	*/
// echo $unitSolrLink;



$mapData = @file('/var/www/html/data/work-in-progress/PSI.Laboratories.New-Old-Names.txt');		// access via Windows+VPN: \\eaw-archives\Lib4RI_DORA_archive$\work-in-progress
if ( @empty($_SERVER['HTTP_HOST']) ) { $mapData = @file('/cygdrive/l/work-in-progress/PSI.Laboratories.New-Old-Names.txt'); }


$newOldAry = array();
foreach( $mapData as $row ) {
	$row = ltrim($row);
	if ( empty($row) || strpos($row,'#') === 0 ) { continue; }
	$ary = explode('=',$row,2);
	$new = trim($ary[0],"\'\" \t\n\r\0\x0B");
	if ( stristr($new,'(example)') ) { continue; }
	$oldAry = array();
	foreach( str_getcsv($ary[1]) as $old ) { $oldAry[] = trim($old,"\'\" \t\n\r\0\x0B"); }
	$newOldAry[ strtolower(str_replace(' ','',$new)) ] = array( 'name' => $new, 'past' => $oldAry );
}
/*
$newOldAry = array(
	"Accelerator Technology ABT"              => array( "Beschleuniger /Konzepte und Entwicklung" ),
	"Radiochemistry LRC"                      => array( "Radio/Umweltchemie" ),
	"Catalysis and Sustainable Chemistry LSK" => array( "Katalyse und nachhaltige Chemie" ),
	"Neutron Scattering and Imaging LNS"      => array( "Neutronenstreuung" ),
	"Macromolecules and Bioimaging LSB"       => array( "Synchrotronstrahlung LSY I" ),
	"Condensed Matter LSC"                    => array( "Synchrotronstrahlung LSY II" ),
	"Neutron and Muon Instrumentation LIN"    => array( "Scientific Development and Novel Materials LDM", "Entwicklung und Methoden" ),
);
*/



module_load_include('inc', 'lib4ri_psi_pub_list', 'includes/queries');


$divAry = array();		// institute configuration 2/2, what is shown and where:
foreach( psi_org_get_divisions() as $divObj ) {
	// skip 7000, it's a clone of "Paul Scherrer Institute PSI"
	if ( intval($divId) == 7000 ) { /* PSI Clone */ continue; }
	// we are going to sort alphabetically, nonetheless ensure "1000 Paul Scherrer Institute PSI" will be on top
	$divIdx = ( intval($divObj->id) == 1000 ? '0' : '' ) . strtolower($divObj->name) . ' ' . $divObj->id;
	$divAry[$divIdx] = array( 'id' => $divObj->id, 'name' => utf8_encode(utf8_decode($divObj->name)) );
}
ksort($divAry);

$unitOkAry = array();
foreach( $divAry as $dAry ) {
	$dIdx = $dAry['id'];  // that's the number e.g. 3000
	$unitOkAry[$dIdx]['name'] = $dAry['name'];
	$unitOkAry[$dIdx]['result'] = array( /* = pubQueryAry */ );

	$labColl = psi_org_get_departments($dIdx);
	if ( sizeof($labColl) ) {
		$labAry = array();
		foreach( $labColl as $labObj ) {
			$labAry[strtolower($labObj->name)] = array( 'id' => $labObj->id, 'name' => utf8_encode(utf8_decode($labObj->name)) );
		}
		ksort($labAry);
	}
	foreach( $labAry as $lAry ) {
		$lIdx = $lAry['id'];  // that's the number e.g. 3000
		$unitOkAry[$dIdx][$lIdx]['name'] = $lAry['name'];
		$unitOkAry[$dIdx][$lIdx]['result'] = array( /* = pubQueryAry */ );
	}
}

// echo "<pre>" . print_r( $unitOkAry ,true) . "</pre><br>";
// echo "<pre>" . print_r( $newOldAry ,true) . "</pre><br>";
// return;

/*
echo "<pre>" . print_r($unitOkAry,1) . "</pre>";
// return;
*/

$queryUnitAry = array(
	'div' => '?f[]=mods_name_personal_affiliation_division_mt:*',
	'lab' => '?f[]=mods_name_personal_affiliation_department_mt:*',
);
	


// vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// in-page functions:
if ( @!function_exists('dora_page_solr_amount') ) {
	function dora_page_solr_amount($query = '',$params = array()) {
		$solr = new IslandoraSolrQueryProcessor();
		$solr->buildQuery( ( empty($query) ? '*:*' : $query ), $params );
		$solr->solrLimit = 1;
		$solr->executeQuery(FALSE);
		return @intval( $solr->islandoraSolrResult['response']['numFound'] );
	}
}
if ( @!function_exists('dora_page_parse_str') ) {
	function dora_page_parse_str($urlSearch) {
		$urlAry = explode('?',$urlSearch,2); // a bit more flexible whether or not there is a question mark.
		parse_str( array_pop($urlAry), $urlAry );
		return $urlAry;
	}
}
if ( @!function_exists('dora_page_make_url') ) {
	function dora_page_make_url($urlRest,$linkLabel = '') {
		if ( $linkLabel == '' ) {
			return ( url('islandora/search/',array('absolute'=>TRUE)) . $urlRest );
		}
		return ( '<a href="' . url('islandora/search/',array('absolute'=>TRUE)) . $urlRest . '" target="_blank">' . $linkLabel . '</a>' );
	}
}
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


foreach ( $unitOkAry as $divId => $divAry ) {
	
	if ( intval($divId) == 7000 ) { /* PSI Clone */ continue; }

	foreach ( $divAry as $labId => $unitData ) {
		
		if ( $labId == 'result' ) { /* results of the parent division */ continue; }
		$isDiv = ( $labId == 'name' );
		$unitId = ( $isDiv ? $divId : $labId );
		$unitLabel = ( $isDiv ? $divAry['name'] : $divAry[$labId]['name'] );

		$unitLabelEnc = ltrim($unitLabel,' 1234567890');
		$unitLabelEnc = str_replace('-','%5c-',$unitLabelEnc);
		$unitLabelEnc = str_replace(':','%5c%3a',$unitLabelEnc);
		$unitLabelEnc = str_replace('/','%5c%2f',$unitLabelEnc);
		$unitLabelEnc = str_replace(' ','%5c%20',$unitLabelEnc);
		
		$combiAry = array( $unitLabelEnc => $unitLabelEnc );
	
		$cacheUnitAry = array();
		if ( $isDiv ) {
			$cacheUnitAry = ( @isset($cacheAry[$divId]['result']) ? $cacheAry[$divId]['result'] : array() );
		} else {
			$cacheUnitAry = ( @isset($cacheAry[$divId][$labId]['result']) ? $cacheAry[$divId][$labId]['result'] : array() );
		}
		$tableAry = $pubQueryAry;

		// get amount of publications
		foreach( $tableAry as $typeLabel => $typeAry ) {
			foreach( $typeAry as $queryLabel => $queryAry ) {
				$query = '';
				$num = -1;
				if ( $_delay < 1 && $cacheAge <= $cacheLifeTime && @isset($cacheUnitAry[$typeLabel][$queryLabel]['count']) && @isset($cacheUnitAry[$typeLabel][$queryLabel]['query']) ) {
					$query = $cacheUnitAry[$typeLabel][$queryLabel]['query'];
					$num = $cacheUnitAry[$typeLabel][$queryLabel]['count'];
				} else {
					if ( $_delay > 0 ) { usleep( $_delay * 1000 ); }
					$query = ( $isDiv ? $queryUnitAry['div'] : $queryUnitAry['lab'] );
					$query = rtrim($query,'*') . $unitLabelEnc . '&' . ltrim($queryAry['query'],'?');
					$num = dora_page_solr_amount( ( strpos($queryAry['query'],'?') ? strtok($queryAry['query'],'?') : '' ), dora_page_parse_str($query) );
				}
		//		echo $typeLabel . " :: " . $queryLabel . " :: " . $num . "<br>";
		//		echo dora_page_make_url($query, $typeLabel.'/'.$queryLabel). "<br>";
				$tableAry[$typeLabel][$queryLabel]['query'] = $query;
				$tableAry[$typeLabel][$queryLabel]['count'] = $num;
			}
		}

		// assign percentages
		foreach( $tableAry as $typeLabel => $typeAry ) {	// get amount
			$max = max( @intval($typeAry['Sum']['count']), @intval($typeAry['Total']['count']), 0 );
			foreach( $typeAry as $queryLabel => $queryAry ) {
				$percent = '';
				if ( $queryLabel == 'Sum' || $queryLabel == 'Total' ) {
					if ( $colAll100 && $max ) {
						$percent = '100.0';
					}
				} elseif ( $_delay < 1 && $cacheAge <= $cacheLifeTime && @isset($cacheUnitAry[$typeLabel][$queryLabel]['percent']) ) {
					$percent = $cacheUnitAry[$typeLabel][$queryLabel]['percent'];
				} else /* if ( $queryAry['count'] > 0 ) */ {
					if ( $queryAry['count'] == 0 ) {
						$percent = ( $max ? '0.0' : '' );
					} elseif ( $queryAry['count'] == $max ) {
						$percent = '100.0';
					} else {
						$percent = number_format( floatval( $queryAry['count'] * 100 / max($max,1) ), 1 );
					}
			//		$percent = $queryAry['count'] . "/" . $max;
				}
				if ( $percent != '' ) {
					$tableAry[$typeLabel][$queryLabel]['percent'] = $percent;
				}
			}
		}

		$cacheUnitAry = array();
		if ( $isDiv ) {
			$resultAry[$divId]['name'] = $unitLabel;
			$resultAry[$divId]['result'] = $tableAry;
		} else {
			$resultAry[$divId][$labId]['name'] = $unitLabel;
			$resultAry[$divId][$labId]['result'] = $tableAry;
		}



	
		$labOldIdx = strtolower(str_replace(' ','',ltrim($unitLabel,' 1234567890')));
		if ( $isDiv || @!isset($newOldAry[$labOldIdx]['past']) ) { continue; }
	//	continue;
		// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// TEST
		
		$newOldAry[$labOldIdx]['_combine'] = array(); // just adding for parsing reasons
		$newOldAry[$labOldIdx]['past']['_combine'] = array(); // just adding for parsing reasons
		

		foreach( $newOldAry[$labOldIdx]['past'] as $unitOldCount => $unitOldLabel ) {


			$labIdOld = $unitId . ( strchr(strval($unitOldCount),'combine') ? '_combine' : chr( intval($unitOldCount) + 97 ) );


			if ( $labIdOld == 'result' ) { /* results of the parent division */ continue; }
			$isDiv = ( $labIdOld == 'name' );
			$unitId = ( $isDiv ? $divId : $labIdOld );
			$unitLabel = ( $isDiv ? $divAry['name'] : ( @isset($divAry[$labIdOld]['name']) ? $divAry[$labIdOld]['name'] : $unitOldLabel ) );
			
			if ( strchr(strval($unitOldCount),'combine') ) { $unitLabel = 'combined'; }
			
			$cacheUnitAry = array();
		/*
			if ( $isDiv ) {
				$cacheUnitAry = ( @isset($cacheAry[$divId]['result']) ? $cacheAry[$divId]['result'] : array() );
			} else {
				$cacheUnitAry = ( @isset($cacheAry[$divId][$labIdOld]['result']) ? $cacheAry[$divId][$labIdOld]['result'] : array() );
			}
		*/
			$tableAry = $pubQueryAry;


			// get amount of publications
			foreach( $tableAry as $typeLabel => $typeAry ) {
				foreach( $typeAry as $queryLabel => $queryAry ) {
					$query = '';
					$num = -1;
					if ( $_delay < 1 && $cacheAge <= $cacheLifeTime && @isset($cacheUnitAry[$typeLabel][$queryLabel]['count']) && @isset($cacheUnitAry[$typeLabel][$queryLabel]['query']) ) {
						$query = $cacheUnitAry[$typeLabel][$queryLabel]['query'];
						$num = $cacheUnitAry[$typeLabel][$queryLabel]['count'];
					} else {
						if ( $_delay > 0 ) { usleep( $_delay * 1000 ); }
						$query = ( $isDiv ? $queryUnitAry['div'] : $queryUnitAry['lab'] );
						$tmpLabel = ltrim($unitOldLabel ,' 1234567890');
						if ( strchr(strval($unitOldCount),'combine') ) {
							$tmpLabel = trim(strchr($query,'='),'=*');
							$query = rtrim($query,'*') . '%22' . implode('%22%20OR%20'.$tmpLabel.'%22',$combiAry) . '%22';
						} else {
							$tmpLabel = str_replace('-','%5c-',$tmpLabel);
							$tmpLabel = str_replace(':','%5c%3a',$tmpLabel);
							$tmpLabel = str_replace('/','%5c%2f',$tmpLabel);
							$tmpLabel = str_replace(' ','%5c%20',$tmpLabel);
							$combiAry[$tmpLabel] = $tmpLabel;
							$query = rtrim($query,'*') . $tmpLabel;
						}
						$query .= '&' . ltrim($queryAry['query'],'?');
						$num = dora_page_solr_amount( ( strpos($queryAry['query'],'?') ? strtok($queryAry['query'],'?') : '' ), dora_page_parse_str($query) );
					}
			//		echo $typeLabel . " :: " . $queryLabel . " :: " . $num . "<br>";
			//		echo dora_page_make_url($query, $typeLabel.'/'.$queryLabel). "<br>";
					$tableAry[$typeLabel][$queryLabel]['query'] = $query;
					$tableAry[$typeLabel][$queryLabel]['count'] = $num;
				}
			}

			// assign percentages
			foreach( $tableAry as $typeLabel => $typeAry ) {	// get amount
				$max = max( @intval($typeAry['Sum']['count']), @intval($typeAry['Total']['count']), 0 );
				foreach( $typeAry as $queryLabel => $queryAry ) {
					$percent = '';
					if ( $queryLabel == 'Sum' || $queryLabel == 'Total' ) {
						if ( $colAll100 && $max ) {
							$percent = '100.0';
						}
					} elseif ( $_delay < 1 && $cacheAge <= $cacheLifeTime && @isset($cacheUnitAry[$typeLabel][$queryLabel]['percent']) ) {
						$percent = $cacheUnitAry[$typeLabel][$queryLabel]['percent'];
					} else /* if ( $queryAry['count'] > 0 ) */ {
						if ( $queryAry['count'] == 0 ) {
							$percent = ( $max ? '0.0' : '' );
						} elseif ( $queryAry['count'] == $max ) {
							$percent = '100.0';
						} else {
							$percent = number_format( floatval( $queryAry['count'] * 100 / max($max,1) ), 1 );
						}
				//		$percent = $queryAry['count'] . "/" . $max;
					}
					if ( $percent != '' ) {
						$tableAry[$typeLabel][$queryLabel]['percent'] = $percent;
					}
				}
			}

			$cacheUnitAry = array();
			if ( $isDiv ) {
				$resultAry[$divId]['name'] = $unitLabel;
				$resultAry[$divId]['result'] = $tableAry;
			} else {
				$resultAry[$divId][$labIdOld]['name'] = $unitLabel;
				$resultAry[$divId][$labIdOld]['result'] = $tableAry;
			}
		}

		unset($newOldAry[$labOldIdx]['_combine']);
		unset($newOldAry[$labOldIdx]['past']['_combine']);
	
		// TEST
		// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	}
}


// cache it (again) if/after life-time is over:
if ( empty($_date) && ( $_delay > 0 || $cacheAge > $cacheLifeTime ) ) {
	file_put_contents($cacheFile, json_encode($resultAry, JSON_PRETTY_PRINT));
}

?>

<style>
#aboutTab3 {
	border-collapse: collapse;
	border-spacing: 1px;
	border: <?php echo ( @intval($_GET['frame']) ? '1px solid #333' : '0px' ); ?>;
	width: 99%;
	padding: 0.5em;
}
#aboutTab3 th {
	line-height:1.5em;
	padding-left:10px;
	padding-right:10px;
	padding-top:5px;
	padding-bottom:5px;
	margin-left:10px;
	margin-right:10px;
	width: 15%;
	align:right;
	vertical-align:top;
	font-weight:300;
	<?php echo ( ( @isset($_GET['grey']) && @empty($_GET['grey']) ) ? '' : 'background-color: #e9e8e7;' ); ?>
}
#aboutTab3 .trMain { 
	border-top: 1px solid #333;
	line-height: 2em;
}
#aboutTab3 td {
	line-height:1.5em;
	padding-left:10px;
	padding-right:10px;
	padding-top:0px;
	padding-bottom:0px;
	margin-left:10px;
	margin-right:10px;
	vertical-align:top;
}
</style>

<?php
// echo "<pre>" . print_r($resultAry,true) . "</pre><br>"; return;
// echo "<table style='position:relative; top:-1em;' id='{$tableTheme}' cellpadding='5'>\r\n";

$htmlAry = array( /* html code lines */ );
// vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// selection form:
if ( @in_array('administrator',array_values($user->roles)) ) {
	$formName = 'dora-page-department-publication-form'; // only used for ids, classes, but NOT from fields
	$today = date("Y-m-d");
	$argAry = array(
		'date'  => $_date,
		'pin'   => @strval($_GET['pin']),
		'delay' => @intval($_GET['delay']),
		'percent' => @intval($_GET['percent']),
	);
	$argStr = '';	// http_build_query($argAry) worked strange...!(?)
	foreach( $argAry as $var => $val ) {
		if ( !empty($var) && !empty($val) ) { $argStr .= ( empty($argStr) ? '?' : '&' ) . $var . '=' . $val; }
	}
	$htmlAry[] = "<div style='text-align:left; position:relative; top:1px; font-size:0.9em;'><form action='{$argStr}' name='{$formName}'><div>";
	$htmlAry[] = "<select name='date' id='edit-{$formName}-select' class='{$formName}-select'>";
	$htmlAry[] = "<option value=''>- Select " . ( intval($_date) && $_date != $today ? 'other' : 'former' ) . " Date -</option>";
	foreach( $cacheList as $cDate => $cTerm ) {
		if ( !intval($_date) && $cDate == $today ) { continue; }
		$htmlAry[] = "<option value='{$cDate}'" . ( $cDate == $_date ? ' selected' : '' ) . ">" . date("M jS, Y",strtotime($cDate)) . "</option>";
	}
	$htmlAry[] = "</select></div>";
	$htmlAry[] = "<div><input type='submit' value='Change Date' name='submit' id='edit-{$formName}-submit' class='{$formName}-submit' style='line-height:0.98em;'>";
	foreach( $argAry as $var => $val ) {
		if ( $var != 'date' && !empty($val) ) { $htmlAry[] = "<input type='hidden' name='{$var}' value='{$val}' />"; }
	}
	$htmlAry[] = "</div></form></div>";
} else {
	$htmlAry[] = ( intval($_date) ? ( 'From: ' . date("M jS, Y",strtotime($_date)) ) : '' ) . '<br>&nbsp;';
}
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


$tableTheme = 'aboutTab3';
echo "<table id='{$tableTheme}' style='position:relative; top:-1em;' cellpadding='5' >\r\n";
// TEST:
// echo "<table border='1' cellpadding='5' cellspacing='0'>";
// echo '<table border="1">';
echo '<tr style="white-space:nowrap;">';
echo 	'<th colspan="2" rowspan="2" style="text-align:left;">' . implode('',$htmlAry) . '</th>';
echo 	'<th colspan="3" style="text-align:left; ' . $cssCol . '">&nbsp;All Publications since 2006</th>';
echo 	'<th colspan="4" style="text-align:left; ' . $cssCol . '">&nbsp;Journal Articles since 2020</th></tr>';
echo '<tr style="white-space:nowrap; ' . $cssRowTop . '">';
foreach( $pubQueryAry as $typeAry ) {
	$css = $cssCol;
	foreach( $typeAry as $dataIdx => $dataAry ) {
		if ( strpos($dataIdx,'_') === 0 ) { continue; }
		$tmp = ( $dataIdx == 'Sum' || $dataIdx == 'Total' ) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : ''; 	// to be tuned...
		echo '<th style="text-align:right; ' . $css. '">&nbsp;' . $tmp . $dataIdx . '&nbsp;</th>';
		$css = '';
	}
}
echo '</tr>';

foreach( $resultAry as $divId => $divAry ) {
	foreach( $divAry as $labId => $unitData ) {

		if ( $labId == 'result' ) { /* results of the parent division */ continue; }
		$isDiv = ( $labId == 'name' );
		$unitId = ( $isDiv ? $divId : $labId );
		$unitLabel = ( $isDiv ? $unitData : $unitData['name'] );
		$tableAry = ( $isDiv ? $divAry['result'] : $divAry[$labId]['result'] );

		if ( $isDiv ) { echo '<tr><td colspan="9"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" style="width:1px; height:1px;"></td><tr>'; }
		elseif ( @isset($showOnlyDiv[intval($divId)]) ) { /* skip laboratories for this division */ continue; }

		if ( $showOnlyCPT && ( $divId == 1000 || $divId == 7000 ) ) { // special case!
			if ( $isDiv ) { /* Lothar wants no Div row */ continue; }
			if ( stripos($unitLabel,'CPT') === false ) { continue; }
		}

		echo '<tr style="white-space:nowrap; vertical-align:top; text-align:right; ' . $cssRow . '">';
		$css = ( $isDiv ? 'padding-top:5px; padding-bottom:0px; ' : 'padding-top:0px; padding-bottom:5px; ' );
		$tmp = '';
		$labIdNum = intval($labId);
		if ( $isDiv ) {
			$tmp = '<b>' . $unitLabel . '</b>';
		} elseif ( strval($labIdNum) != strval($labId) ) { // if it is '3700a' resp. old Lab name
			if ( !$showBracket || stripos($unitLabel,'combined') === false ) {
				$tmp = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $unitLabel;
			} else {
				$tmp = '<i>' . $unitLabel . '</i><a href="#remark">' . $combiAsterik . '</a>';
				$css .= 'color:#111; text-align:right; ';
			}
		} else {
			$tmp = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $unitLabel;
		}
		echo '<td style="text-align:left; ' . $css . ( $_inst == 'eawag' ? 'width:25%;' : 'width:20%;' ) . '">' . $tmp . '</td>';
		$labOldCount = -1;
		if ( strval($labIdNum) == strval($labId) ) { // if it is '3700' the find/count old labs
			foreach( $divAry as $idx => $data ) {
				if ( $labIdNum == intval($idx) ) { $labOldCount++; }
			}
		}
		if ( !$showBracket || $isDiv || $labOldCount == 0 ) {
			echo '<td>' . '&nbsp;<!-- keep for Firefox -->&nbsp;&nbsp;' . '</td>';
		} elseif ( $labOldCount > 0 ) {
			$tmpAry = array();
			for( $i=0;$i<$labOldCount;$i++) {
 				$tmpAry[] = '<img style="width:15px; height:52px; position:relative; top:' . floatval( 0.6 - (floatval($i)*0.35) ) . 'em; z-index:' . ($i+1) . ';" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAA0BAMAAACnRX8XAAAAMFBMVEUAAAC7u7vp6enHx8fT09Ph4eHW1tYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADprfIRAAAAEHRSTlMA////////AAAAAAAAAAAAzXmHfQAAAAlwSFlzAAAOxAAADsQBlSsOGwAAADdJREFUGJVjUFFggABHEyjLUdgJwlAWFIMIMRkKwoVEGKBCwgEwIQcIg0XQAConKAA1c5QxEAwA3hAJhNKGF94AAAAASUVORK5CYII=" width="15" height="52">';
			}
  			$tmpAry[] = '<img style="width:15px; height:16px; position:relative; top:' . floatval( 0.3 - (floatval($labOldCount)*0.35) ) . 'em; z-index:' . ($labOldCount+1) . ';" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAQBAMAAAA7eDg3AAAAMFBMVEUAAAC7u7vp6enHx8fT09Ph4eHW1tYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADprfIRAAAAEHRSTlMA////////AAAAAAAAAAAAzXmHfQAAAAlwSFlzAAAOxAAADsQBlSsOGwAAADlJREFUCJljYGBgEBRggADSGEJQhqEIlOEorABhBAsaQRgsglAhJkeYkBpcyFnQASKkYghlMKgoAAD8rwTgqYUYagAAAABJRU5ErkJggg==" width="15" height="16">';
			echo '<td rowspan="' . ( $labOldCount + 1 ) . '" style="line-height:0.75em;">' . implode('<br>',$tmpAry) . '</td>';
		}
		foreach( $tableAry as $typeAry ) {
			$css = $cssCol;
			foreach( $typeAry as $dataIdx => $dataAry ) {
				if ( strpos($dataIdx,'_') === 0 ) { continue; }
				$tmp = number_format($dataAry['count'],0,'.','&thinsp;');
				echo '<td style="white-space:nowrap;' . $css. '">&nbsp;' . dora_page_make_url($dataAry['query'],$tmp);
				if ( @intval($_GET['percent']) < 1 && !$isDiv && strval($labIdNum) != strval($labId) ) { // if it is '3700a' resp. old Lab name
					echo ( intval($_GET['percent']) < 0 ? '' : '&nbsp;<br>&nbsp;' ) . '</td>'; // no percent Lothar said for old labs and 'combinedd' row
				} elseif ( $dataAry['percent'] != '' ) {
					echo '&nbsp;<br>' . ( strpos($percentFormat,'%') !== false ? str_replace('%',$dataAry['percent'].'%',$percentFormat) : $dataAry['percent'].'%' ) . '</td>'; 
				} else { // ensure a 'safe' space otherwise:
					echo '&nbsp;<br>' . '&nbsp;' . '</td>';
				}
				$css = '';
			}
		}
		echo '</tr>';
	/*
		if ( @!isset($newOldAry[strtolower(str_replace(' ','',$unitLabel))]) ) { continue; }

		echo '<tr style="white-space:nowrap; text-align:right; ' . $cssRow . '">';
		$css = ( $isDiv ? 'padding-top:5px; padding-bottom:0px; ' : 'padding-top:0px; padding-bottom:5px; ' );
		$tmp = ( $isDiv ? '<b>'.$unitLabel.'</b>' : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$unitLabel );
		echo '<td style="text-align:left; ' . $css . ( $_inst == 'eawag' ? 'width:25%;' : 'width:20%;' ) . '">' . $tmp . '</td>';
		foreach( $tableAry as $typeAry ) {
			$css = $cssCol;
			foreach( $typeAry as $dataIdx => $dataAry ) {
				if ( strpos($dataIdx,'_') === 0 ) { continue; }
				$tmp = number_format($dataAry['count'],0,'.','&thinsp;');
				echo '<td style="white-space:nowrap;' . $css. '">&nbsp;' . dora_page_make_url($dataAry['query'],$tmp) . '&nbsp;<br>';
		//		echo '&nbsp;' . $dataAry['percent'] . '&nbsp;</td>';
				if ( $dataAry['percent'] != '' ) {
					echo ( strpos($percentFormat,'%') !== false ? str_replace('%',$dataAry['percent'].'%',$percentFormat) : $dataAry['percent'].'%' ) . '</td>'; 
				} else { // ensure a 'safe' space otherwise:
					echo '&nbsp;' . '</td>';
				}
				$css = '';
			}
		}
		echo '</tr>';
	*/

	}
}
echo '</table>';


echo '<div style="position:relative; left:0.75em;">';

if ( $cacheLifeTime > 60 ) {
	echo 'The data is updated every hour. Therefore, small discrepancies to the actual values are possible.' . "<br>";
} else {
	echo 'Usually the data is updated every hour. As <b>' . ucwords($vipRole) . '</b> however you always get the newest values.' . "<br>";
}
echo '<a name="remark">' . $combiAsterik2 . '</a>: ' . $combiRemark;

echo '</div></br>';
?>
