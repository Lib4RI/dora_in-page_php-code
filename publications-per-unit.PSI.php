<?php

$useBracket = true; // = @isset($_GET['bracket']);
$tableGrey = false;	// = @!isset($_GET['blank']);
$colorAry = array( '#e8e7e6', '#eCeBeA', '#f1f0ef' );	// Only used if $tableGrey is TRUE.

/*
	# Hello, this is a comment!
	"Current Laboratory Name (example)"        =  "Old Laboratory Name (example)" , "Acient Laboratory Name (example)" , "Stone-age Laboratory Name (example)"
	"Radiochemistry LRC"                       =  "Radio/Umweltchemie"
	"Neutron and Muon Instrumentation LIN"     =  "Scientific Development and Novel Materials LDM" ,  "Entwicklung und Methoden"
*/
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
	$newOldAry[$new] = $oldAry;
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

if ( !function_exists('pubListSolrHref') ) {
	function pubListSolrHref($unitName,$isLab = 2) {
		$solrHost = ( strpos($_SERVER['HTTP_HOST'],'prod') ? 'dora' : 'dora-dev' );
		$solrField = $isLab ? 'mods_name_personal_affiliation_department_mt' : 'mods_name_personal_affiliation_division_mt';
		$solrLink = "https://www.{$solrHost}.lib4ri.ch/psi/islandora/search/{$solrField}:";
		if ( !is_array($unitName) ) {
			$query = rawurlencode('"'.$unitName.'"');
			return ( "<a href='{$solrLink}{$query}' target='_blank'>{$unitName}</a>" );
		}
		$query = '';
		foreach($unitName as $uName ) {
			if ( !empty($query) ) { $query .= '+OR+'; }
			$query .= rawurlencode('"'.$uName.'"');
		}
		return ( "&nbsp;<a href='{$solrLink}({$query})' target='_blank'>combine</a>" );
	}
}

module_load_include('inc', 'lib4ri_psi_pub_list', 'includes/queries');



$tmpAry = array();
foreach( psi_org_get_divisions() as $divObj ) {
	$divIdx = strtolower($divObj->name) . ' ' . $divObj->id;
	$tmpAry[$divIdx] = array( 'id' => $divObj->id, 'name' => utf8_encode(utf8_decode($divObj->name)) );
}
$divAry = array( array_shift($tmpAry) );
ksort($tmpAry);
foreach( $tmpAry as $idx => $ary ) { 
	if ( substr($idx,0,1) != 'l' ) { $divAry[] = $ary; }
}
foreach( $tmpAry as $idx => $ary ) { // keep!
	if ( substr($idx,0,1) == 'l' ) { $divAry[] = $ary; }
}


// HTML Output
$tagDiv = "<div style='margin-top:0.35em; margin-bottom:0.35em;'>";
echo "<table border='0' cellpadding='0' cellspacing='0'>";
foreach( $divAry as $dAry ) {
	$labColl = psi_org_get_departments($dAry['id']);
	if ( sizeof($labColl) < 1 ) { continue; }

	$labAry = array();
	foreach( $labColl as $labObj ) {
		$labAry[strtolower($labObj->name)] = utf8_encode(utf8_decode($labObj->name));
	}
	ksort($labAry);

	echo "<tr style='white-space:nowrap; background-color:" . ( $tableGrey ? $colorAry[0] : '' ) . ";'>";
	echo "<td colspan='7'>{$tagDiv}<b>" . ( $tableGrey ? '&nbsp;' : '' ) . pubListSolrHref( $dAry['name'],0) . "</b></div></td><td width='99%' style='background-color:#f6f5f4;'></td>\r\n";
	$labCount = 0;
	foreach( $labAry as $labName ) {
		echo "<tr style='white-space:nowrap; background-color:" . ( $tableGrey ? ( empty( (++$labCount) % 2 ) ? $colorAry[1] : $colorAry[2] ) : '' ) . ";'>";
		echo "<!-- col1 --><td>&nbsp;" . ( $tableGrey ? '&nbsp;' : '' ) . "&nbsp;</td>";
		echo "<!-- col2 --><td style='white-space:nowrap; vertical-align:top;'>{$tagDiv}&nbsp;" . pubListSolrHref($labName,1) . "</div></td>";
		echo "<!-- col3 --><td>&nbsp;&nbsp;</td>";
		echo "<!-- col4 --><td style='vertical-align:top;'><!-- col4 -->{$tagDiv}";
		if ( $sumLab = @sizeof($newOldAry[$labName]) ) {
			echo implode('<br>',array_map('pubListSolrHref',$newOldAry[$labName])) . '';
		}
		echo "</div></td>";
		$numOld = @sizeof($newOldAry[$labName]);		// can me empty/inexisting
		$symbol = ( $numOld ? '&lt;' : '&nbsp;' );
		if ( $numOld && $useBracket ) {
			$bracketAry = array(
				0 => '',
				1 => "<b style='font-size:1em; color:#333;'>]</b>",
				2 => "<b style='font-size:1.35em;'><span style='position:relative; top:0.4em;'>&rceil;</span></br><span style='position:relative; top:-0.4em;'>&rfloor;</span></b>",
			);
			$symbol = $bracketAry[min($numOld,2)];
			for($b=2;$b<$numOld;$b++) { $symbol = str_replace("</span></br>","</span></br><span style='color:#333; position:relative; left:0.125em;'>|</span><br>",$symbol); }
		}
		if ( $numOld == 1 ) { $bracket = "<span style='font-size:0.875em; color:#333; font-weight:600;'>]</span>";}
		echo "<!-- col5 --><td align='right'>&nbsp;&nbsp;&nbsp;{$symbol}</td>";
		$html = ( $numOld ? pubListSolrHref(array_merge(array('new'=>$labName),$newOldAry[$labName]),1) : '<!-- no old name -->' );
		echo "<!-- col6 --><td style='vertical-align:middle;'>{$tagDiv}<i>" . $html . "</i></div></td>";
		echo "<!-- col7 --><td>&nbsp;&nbsp;</td>";
		echo "<!-- col8 --><td width='99%' style='background-color:#f6f5f4;'></td></tr>\r\n";
	}
	echo "<tr><td colspan='8'>&nbsp;</td></tr>\r\n";
}
echo "</table>\r\n";

?>
