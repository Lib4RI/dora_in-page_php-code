<?php				/* Browse by Eawag/Empa/WSL Organizational Units	*/
// Restrictive access when producing a cache file via URL:
if ( @intval($_GET['delay']) && !user_is_logged_in() && strval($_GET['pin']) != '7kPdjE4Li' ) { echo 'Hello!<br>You are not allowed to access this page this way!<br><br>'; return; }

$cacheLifeTime = 7200;		// seconds (how long to respect dumped file with cached data), should be some longer than (optional) (p)re-caching with Crontab:
// 15 */2 * * * wget -q -b -t 1 -U "Mozilla/5.0 (Lib4ri/Crontab)" "http://lib-dora-prod1.emp-eaw.ch/empa/department-publication?pin=7kPdjE4Li&delay=250" --delete-after -O "/tmp/empa.pub-per-unit.html"

$percentFormat = '<div style="font-size:0.95em; color:#888;">&nbsp;%&nbsp;</div>';		// 2021-05-05: (visually) same size as linked number, grey, no brackets

$cssCol = 'border-left: 1px solid #e2e1e0;';
$cssRow = 'border-bottom: 1px solid #fFfEfD;';
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
$unitSolrLink .= '&q=PID:' . ( empty($_inst) ? '*' : $_inst ) . '-units%5c%3a*+NOT+PID:*collect*&fl=PID%2c+MADS_department_mt';

// Put Solr's (csv) response into an array/collection and filtering-out it 'on the fly' for units to be suppressed:
foreach ( @file($unitSolrLink) as $row ) {
	$rowAry = str_getcsv(rtrim($row),'|');
	if ( ( empty($_inst) || strpos($rowAry[0],$_inst) === 0 ) && !in_array($rowAry[0],$unitOmitAry) ) {
		$unitLabel = ( @empty($rowAry[2]) ? $rowAry[1] : array_slice($rowAry,1,sizeof($rowAry)) );
		if ( empty($unitLabel) /* only seen on DEV */ ) { continue; }
		$unitOkAry[$rowAry[0]] = $unitLabel;
	}
}
asort($unitOkAry);

$queryUnitAll = '?f[]=mods_name_personal_nameIdentifier_organizational%5c%20unit%5c%20id_ms:*';


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


foreach ( $unitOkAry as $unitId => $unitLabel ) {

	$cacheUnitAry = ( @isset($cacheAry[$unitId]) ? $cacheAry[$unitId] : array() );
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
				$query = rtrim($queryUnitAll,'*') . str_replace('-','%5c-',str_replace(':','%5c%3a',$unitId)) . '&' . ltrim($queryAry['query'],'?');
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
	
	$resultAry[$unitId] = $tableAry;
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
	padding-left:10px;
	padding-right:10px;
	padding-top:5px;
	padding-bottom:5px;
	margin-left:10px;
	margin-right:10px;
	vertical-align:top;
}
</style>

<?php
// echo "<pre>" . print_r($resultAry,true) . "</pre><br>";
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
// echo '<table border="1">';
echo '<tr style="white-space:nowrap;">';
echo 	'<th rowspan="2" style="text-align:left;">' . implode('',$htmlAry) . '</th>';
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

foreach( $resultAry as $unitId => $tableAry ) {
	echo '<tr style="text-align:right; ' . $cssRow . '">';
	echo '<td style="text-align:left; ' . ( $_inst == 'eawag' ? 'width:60%' : 'width:50%' ) . '">' . $unitOkAry[$unitId] . '</td>';
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

}
echo '</table>';

if ( $cacheLifeTime > 60 ) {
	echo "&nbsp; " . 'The data is updated every hour. Therefore, small discrepancies to the actual values are possible.' . "<br><br>";
} else {
	echo "&nbsp; " . 'Usually the data is updated every hour. As <b>' . ucwords($vipRole) . '</b> however you always get the newest values.' . "<br><br>";
}
?>
