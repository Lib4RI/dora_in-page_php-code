<p>
 <a href='/' target='_blank'>DORA</a> is the institutional repository and bibliography for all research articles and other publications affiliated 
with the four research institutes within the ETH Domain (<a href='https://www.eawag.ch/' target='_blank'>Eawag</a>, <a href='https://www.empa.ch/' target='_blank'>Empa</a>, <a href='https://www.wsl.ch/' target='_blank'>WSL</a> and <a href='https://www.psi.ch/' target='_blank'>PSI</a>) and hosted by the <a href='https://www.lib4ri.ch/' target='_blank'>Lib4RI</a>. DORA is based on the open source software framework <a href='https://islandora.ca/' target='_blank'>Islandora</a>, which uses Drupal, Fedora and SOLR as components. As a service to our users we developed an <a href='https://www.lib4ri.ch/sites/default/files/media/documents/poster_openrepos2019.pdf' target='_blank'>ingestion workflow</a>, which you can find freely available on <a href='https://github.com/Lib4RI/pub_db_lib' target='_blank'>GitHub</a>.
<br> 
<span style='margin-bottom:0px; position:relative; top:1.25em'>DORA acts simultaneously as:</span>
<ul style='margin-bottom:0px; position:relative; top:-0.25em'>
	<li><b>Bibliography</b> &mdash; DORA records the scientific publications produced at the research institutes 
		and is a source for publication lists on the institutional websites and for academic reports</li>
	<li><b>Archive</b> &mdash; DORA preserves the full text of the institutes publications 
		and makes them freely available to all internal members</li>
	<li><b>Open Access (OA) Repository</b> &mdash; Researchers are able to make a full text version 
		of their scientific articles freely available in DORA (green road to OA), 
		thus facilitating compliance with the OA policies of many research funders</li>
</ul>

<?php			/* Solr queries to fill a HTML table with Stats of Journal Articles and Publicatiosns generally in DORA	*/
// Restrictive access when producing a cache file via URL:
if ( @intval($_GET['delay']) && !user_is_logged_in() && strval($_GET['pin']) != '7kPdjE4Li' ) { echo 'Hello!<br>You are not allowed to access this page this way!<br><br>'; return; }


// -------------------------------------------------------------------------------
// Content related settings:

$yearSpan = 3;	// how many years back we are going to show figures

// user defaults, not changed by code:
$maySelectDate = true;

// user defaults, may be technically reset however
$cacheLifeTime = 21600;		// seconds (how long to respect/read dumped file with cached data). Almost no caching for Admins, Repor mamagers and Editors (3 sec), adjusted below.
$jsAnimated = true;
$readFromCache = true;		

$tableTheme = 'aboutTab3';
$percentFormat = '<div style="font-size:0.95em; color:#888;">%</div>';		// 2021-05-05: (visually) same size as linked number, grey, no brackets

$noteIcon1a = '<sup style="font-size:0.75em; font-weight:500; position:relative; top:-0.65em; left:0.35em;">1</sup>';
$noteIcon1b = '<sup style="font-size:0.75em; font-weight:500; position:relative; top:-0.45em;">1)</sup>';  // bottom
$noteText1 = 'In this column only publications by PSI authors. Publications relating to PSI facilities compiled by external authors only are listed separately in the last column.';


$tableViewsDLs = array(
	'row_view_20' => array(
			'Views in 2020',
		'Eawag' => array( 'link' => '', 'result' => 258663, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 196841, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 167662, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 361572, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 22809, 'query' => '' ),
	),
	'row_dl_20' => array(
			'<!-- no top line -->Downloads in 2020',
		'Eawag' => array( 'link' => '', 'result' => 207379, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 150960, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 132343, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 248214, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 17720, 'query' => '' ),
	),
	'row_view_21' => array(
			'Views in 2021',
		'Eawag' => array( 'link' => '', 'result' => 392886, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 329088, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 385085, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 518176, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 49936, 'query' => '' ),
	),
	'row_dl_21' => array(
			'<!-- no top line -->Downloads in 2021',
		'Eawag' => array( 'link' => '', 'result' => 277885, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 260922, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 300786, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 377649, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 35709, 'query' => '' ),
	),
	'row_view_22' => array(
			'Views in 2022',
		'Eawag' => array( 'link' => '', 'result' => 474832, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 436215, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 531576, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 699558, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 67405, 'query' => '' ),
	),
	'row_dl_22' => array(
			'<!-- no top line -->Downloads in 2022',
		'Eawag' => array( 'link' => '', 'result' => 295462, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 275650, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 340854, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 430412, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 47132, 'query' => '' ),
	),
	'row_view_23' => array(
			'Views in 2023',
		'Eawag' => array( 'link' => '', 'result' => 265781, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 271734, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 247808, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 485534, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 34898, 'query' => '' ),
	),
	'row_dl_23' => array(
			'<!-- no top line -->Downloads in 2023',
		'Eawag' => array( 'link' => '', 'result' => 201926, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 216972, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 185399, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 368564, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 30498, 'query' => '' ),
	),
	'row_view_24' => array(
			'Views in 2024',
		'Eawag' => array( 'link' => '', 'result' => 358767, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 361982, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 378972, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 581800, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 53340, 'query' => '' ),
	),
	'row_dl_24' => array(
			'<!-- no top line -->Downloads in 2024',
		'Eawag' => array( 'link' => '', 'result' => 205066, 'query' => '' ),
		'Empa' => array( 'link' => '', 'result' => 235595, 'query' => '' ),
		'PSI'  => array( 'link' => '', 'result' => 219700, 'query' => '' ),
		'WSL'  => array( 'link' => '', 'result' => 403922, 'query' => '' ),
		'_sum' => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 'link' => '', 'result' => 33941, 'query' => '' ),
	),
	/*
		VIEW NUMBERS VIA REPORTS FROM ISLANDORA USAGE STATS:
	https://www.dora.lib4ri.ch/eawag/admin/reports/lib4ri_view_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
	https://www.dora.lib4ri.ch/empa/admin/reports/lib4ri_view_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
	https://www.dora.lib4ri.ch/psi/admin/reports/lib4ri_view_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
		For PSI views better use: https://www.dora.lib4ri.ch/psi/stats.vw
	https://www.dora.lib4ri.ch/wsl/admin/reports/lib4ri_view_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=

		DOWNLOAD NUMBERS VIA REPORTS FROM ISLANDORA USAGE STATS:
	https://www.dora.lib4ri.ch/eawag/admin/reports/lib4ri_downloads_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
	https://www.dora.lib4ri.ch/empa/admin/reports/lib4ri_downloads_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
	https://www.dora.lib4ri.ch/psi/admin/reports/lib4ri_downloads_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
		For PSI DLs better use: https://www.dora.lib4ri.ch/psi/stats.dl
	https://www.dora.lib4ri.ch/wsl/admin/reports/lib4ri_downloads_stats_details?time_op=between&time%5Bvalue%5D%5Bdate%5D=&time%5Bmin%5D%5Bdate%5D=01%2F01%2F2024&time%5Bmax%5D%5Bdate%5D=12%2F31%2F2024&uid_op=%3D&uid=0&pid_op=contains&pid=&ip_op=contains&ip=
	*/
);


$_delay = @intval($_GET['delay']);	// Delay between Solr two queries, in milli-seconds

// Solr queries, compatible with Solr web interface, for IslandoraQueryProcessor they may be overtuned (filters by content model, ...)
// resp. there may be differences between Solr web interface and IslandoraQueryProcessor - $queryPidBase shoud work for both:
$queryPidBase = "( PID:@inst%5c%3a1* OR PID:@inst%5c%3a2* OR PID:@inst%5c%3a3* OR PID:@inst%5c%3a4* OR PID:@inst%5c%3a5* OR PID:@inst%5c%3a6* OR PID:@inst%5c%3a7* OR PID:@inst%5c%3a8* OR PID:@inst%5c%3a9* )";
$queryPidTune = " NOT RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C/@inst%5C%3Adeleted%22";
$queryPidTune .= " NOT RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C/@inst%5C%3Astaged%22"; // so far only *essential* for PSI, but won't harm
$queryPid = $queryPidBase . $queryPidTune;  // using '@inst' as placeholder for the institute's abbbreviation


$sepChar = @empty($_GET['sep']) ? '&thinsp;' : urldecode($_GET['sep']);		// = character/symbol to separate 100 from 1000

if ( @!function_exists('about_page_solr_query') ) {
	function about_page_solr_query($query,$inst) {
		$solr = new IslandoraSolrQueryProcessor();
		$solr->buildQuery( str_replace('@inst',strtolower($inst),$query) );
		$solr->solrLimit = 1;
		$solr->executeQuery(FALSE);
		return @intval( $solr->islandoraSolrResult['response']['numFound'] );
	}
}

if ( @!function_exists('about_page_number_apo') ) {		// optimized, but similar result like number_format()
	function about_page_number_apo($val, $sepChr = "'") {
		$str = @strval($val);
		if ( empty($sepChr) || intval($val) < 1000 ) { return $str; }
		$term = '';
		while( !empty($str) && ( $part = substr($str,-3) ) ) {  // 3 for steps by 10^3
			$str = substr($str,0,-3);
			$term = ( strlen($str) ? $sepChr : '' ) . $part . $term;
		}
		return $term;
	}
}

if ( @!function_exists('about_page_delayed') ) {
	function about_page_delayed($term,$animJS = false) {
		if ( !$animJS ) {
			return $term . '<!-- ' . $term . ' -->';
		}
		// let's indicate visually that these are real-time data:
		$img = "<img src='https://upload.wikimedia.org/wikipedia/commons/d/de/Ajax-loader.gif' style='width:15px; height:15px; position:relative; top:1px' vspace='" . ( strpos($term,'<br>') ? '13' : '0' ) . "' />";
		return '<noscript>'.$term.'</noscript>' . '<script> document.write("'.$img.'"); </script>' . '<!-- '.$term.' -->';
	}
}


$queryPidTmp = "( PID:eawag%5c%3a* OR PID:empa%5c%3a* OR PID:psi%5c%3a* OR PID:wsl%5c%3a* )";   // . str_replace('@inst','*',$queryPidTune); <== * won't work here!
foreach( array('eawag','empa','psi','wsl') as $idx => $inst ) {
	$queryPidTmp .= str_replace('@inst',$inst,$queryPidTune);		// exclude them 1 per institute!
}

$overviewAry = array(	/* special array to keep data used for the page intro/overview above the table */
	'Total Records' => array( 
		'query' => $queryPidTmp,
		'link' => '',		/* no link defined, would lead to the main site too */
		'result' => -1, 	 	/* TBA */
		'percent' => '',		/* TBA */
	),
	'With full text' => array(
		'query' => $queryPidTmp . ' NOT RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text',
		'link' => '',		/* no link defined, would lead to the main site too */
		'result' => -1, 	 	/* TBA */
		'percent' => '',		/* TBA */
	),
	'Open Access'  => array(
		'query' => $queryPidTmp . ' AND RELS_EXT_fullText_literal_ms:Open%5c%20Access',
		'link' => '',		/* no link defined, would lead to the main site too */
		'result' => -1, 	 	/* TBA */
		'percent' => '',		/* TBA */
	),
);
foreach( $overviewAry as $oType => $oAry ) {
	if ( $_delay > 0 ) { usleep( $_delay * 1000 ); }
	$overviewAry[$oType]['result'] = about_page_solr_query($oAry['query']);
}
foreach( $overviewAry as $oType => $oAry ) {
	$overviewAry[$oType]['percent'] = ( $oType == 'Total Records' ) ? '100.0' : number_format( floatval( $oAry['result'] / max($overviewAry['Total Records']['result'],1) * 100 ), 1 );
}

?>


<!-- DORA about page, stats table --->
<style>
#aboutTab0 {
	border-collapse: collapse;
	border-spacing: 0px;
	width: 99%;
}
#aboutTab0 th {
	padding: 0.5em;
}
#aboutTab0 td {
	padding: 0.5em;
}

#aboutTab1 {
	border-collapse: collapse;
	border: 1px solid #e3e2e1;
	border-spacing: 0px;
	width: 99%;
}
#aboutTab1 tr:nth-child(odd) { 
	background-color: #e9e8e7;
}
#aboutTab1 th {
	padding: 0.5em;
	border: 1px solid #e9e8e7;
	background-color: #e3e2e1;
	font-weight:300;
}
#aboutTab1 td {
	padding: 0.5em;
	border: 1px solid #e9e8e7;
}

#aboutTab2 {
	border-collapse: collapse;
	border-spacing: 0px;
	width: 99%;
}
#aboutTab2 th {
	padding: 0.5em;
	background-color: #e9e8e7;
	font-weight:300;
}
#aboutTab2 td {
	padding: 0.5em;
}

#aboutTabs3aux {
	border-collapse: collapse;
	border-spacing: 0px;
	border-padding: 0px;
	cell-padding: 0px;
	border: 1px solid #333;
	width: 1%;
	padding: 0em;
}

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
// REMOVE if FINAL:
// if ( !user_is_logged_in() ) { echo "<br>Sorry, <i>currently</i> you must be logged in to see this!<br><br>"; return; }


// which host/server? :
$host = ( strpos($_SERVER['HTTP_HOST'],'-prod') ? 'dora.lib4ri.ch' : 'dora-dev.lib4ri.ch' );
$yearNow = intval(date("Y"));

// search links:
$linkAll = 'https://www.' . $host . '/@inst/islandora/search?islandora_solr_search_navigation=1&f[0]=mods_genre_ms:*';
$linkFT = 'https://www.' . $host . '/@inst/islandora/search?islandora_solr_search_navigation=1&f[0]=-RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text';
$linkOA = 'https://www.' . $host . '/@inst/islandora/search?islandora_solr_search_navigation=1&f[0]=RELS_EXT_fullText_literal_ms:Open%5c%20Access';
$linkAV = 'https://www.' . $host . '/@inst/islandora/search?islandora_solr_search_navigation=1&f[0]=RELS_INT_lib4ridora-multi-embargo-document_version_literal_mt:Accepted%5c%20Version';

// For every year >=2020 we are going to make global variables like: $_linkJA21, $_linkJA21oa, $_linkJA21emb, $_linkJA21res
$queryAry['JA'] = array(	/* example query links for year 2077: */
	'all' => 'https://www.' . $host . '/@inst/islandora/search/?f[0]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2077-01-01T00%3A00%3A00Z%20TO%202077-12-31T23%3A59%3A59Z]&f[1]=mods_genre_ms%3A%22Journal%20Article%22',
	'oa'  => 'https://www.' . $host . '/@inst/islandora/search/?f[0]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2077-01-01T00%3A00%3A00Z%20TO%202077-12-31T23%3A59%3A59Z]&f[1]=mods_genre_ms%3A%22Journal%20Article%22&f[2]=RELS_EXT_fullText_literal_ms:Open%5C%20Access',
	'emb' => 'https://www.' . $host . '/@inst/islandora/search/?f[0]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2077-01-01T00%3A00%3A00Z%20TO%202077-12-31T23%3A59%3A59Z]&f[1]=mods_genre_ms%3A%22Journal%20Article%22&f[2]=RELS_EXT_fullText_literal_ms:Restricted%5C%20%5C%28Embargo%5C%29',
	'res' => 'https://www.' . $host . '/@inst/islandora/search/?f[0]=mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2077-01-01T00%3A00%3A00Z%20TO%202077-12-31T23%3A59%3A59Z]&f[1]=mods_genre_ms%3A%22Journal%20Article%22&f[2]=RELS_EXT_fullText_literal_ms:Restricted%20OR%20RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text',
);
for($y=20;$y<=$yearNow;$y++) {
	foreach( array_keys($queryAry['JA']) as $suffix ) {
		$varNew = '_link' . 'JA' . $y . ( $suffix == 'all' ? '' : $suffix );
		$$varNew = str_replace('77',$y,$queryAry['JA'][$suffix]);
	}
}

// table structure as array, row-wise:
$tableSearchLinks = array(
	'row_repo' => array(
		'<!-- no top line -->&nbsp;',
		'<a href="https://www.' . $host . '/eawag/" target="_blank">DORA' . ( @intval($_GET['break']) ? '<br>' : ' ' ) . 'Eawag</a>',
		'<a href="https://www.' . $host . '/empa/" target="_blank">DORA' . ( @intval($_GET['break']) ? '<br>' : ' ' ) . 'Empa</a>',
		'<a href="https://www.' . $host . '/psi/" target="_blank" title="' . $noteText1 . '">DORA' . ( @intval($_GET['break']) ? '<br>' : ' ' ) . 'PSI</a>' . '<a href="#noteIcon1" title="' . $noteText1 . '">' . $noteIcon1a . '</a>',
		'<a href="https://www.' . $host . '/wsl/" target="_blank">DORA' . ( @intval($_GET['break']) ? '<br>' : ' ' ) . 'WSL</a>',
		'Total' . ( @intval($_GET['break']) ? '<br>&nbsp;' : '' ),
		'&nbsp;Publications&nbsp;related<br>to&nbsp;PSI&nbsp;facilities',
	),
	
	'row_pub_all' => array(
			'Publications',
		'Eawag' => array( 'link' => $linkAll, 'result' => -1, 'query' => $queryPid ),
		'Empa' => array( 'link' => $linkAll, 'result' => -1, 'query' => $queryPid ),
		'PSI'  => array( 'link' => $linkAll . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22' ),
		'WSL'  => array( 'link' => $linkAll, 'result' => -1, 'query' => $queryPid ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $linkAll . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
		),
	),
	'row_pub_ft' => array(
			'<!-- no top line -->&nbsp; &nbsp; With full text',
		'Eawag' => array( 'link' => $linkFT, 'result' => -1, 'query' => $queryPid . ' NOT RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text' ),
		'Empa' => array( 'link' => $linkFT, 'result' => -1, 'query' => $queryPid . ' NOT RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text' ),
		'PSI'  => array( 'link' => $linkFT . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 NOT RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text' ),
		'WSL'  => array( 'link' => $linkFT, 'result' => -1, 'query' => $queryPid . ' NOT RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array( 
			'link' => $linkFT . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal NOT RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text',
		),
		'100%' => 'row_pub_all',
	),
	'row_pub_oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $linkOA, 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_fullText_literal_ms:Open%5c%20Access' ),
		'Empa' => array( 'link' => $linkOA, 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_fullText_literal_ms:Open%5c%20Access' ),
		'PSI'  => array( 'link' => $linkOA . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access' ),
		'WSL'  => array( 'link' => $linkOA, 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_fullText_literal_ms:Open%5c%20Access' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $linkOA . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access',
		),
		'100%' => 'row_pub_all',
	),
	'row_pub_av' => array(
			'<!-- no top line -->&nbsp; &nbsp; Accepted Version',
		'Eawag' => array( 'link' => $linkAV, 'result' => -1, 'query' => $queryPid . ' AND RELS_INT_lib4ridora-multi-embargo-document_version_literal_mt:Accepted%5c%20Version' ),
		'Empa' => array( 'link' => $linkAV, 'result' => -1, 'query' => $queryPid . ' AND RELS_INT_lib4ridora-multi-embargo-document_version_literal_mt:Accepted%5c%20Version' ),
		'PSI'  => array( 'link' => $linkAV . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_INT_lib4ridora-multi-embargo-document_version_literal_mt:Accepted%5c%20Version' ),
		'WSL'  => array( 'link' => $linkAV, 'result' => -1, 'query' => $queryPid . ' RELS_INT_lib4ridora-multi-embargo-document_version_literal_mt:Accepted%5c%20Version' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $linkAV . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_INT_lib4ridora-multi-embargo-document_version_literal_mt:Accepted%5c%20Version',
		),
		'100%' => 'row_pub_all',
	),
	'row_pub_ja20' => array(
			'Journal Articles in 2020',
		'Eawag' => array( 'link' => $_linkJA20, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA20, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA20 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA20, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA20 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]',
		),
	),
	'row_pub_ja20oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $_linkJA20oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA20oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA20oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA20oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA20oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja20',
	),
	'row_pub_ja20emb' => array(
			'<!-- no top line -->&nbsp; &nbsp; With embargo',
		'Eawag' => array( 'link' => $_linkJA20emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA20emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA20emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA20emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA20emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja20',
	),
	'row_pub_ja20res' => array(
			'<!-- no top line -->&nbsp; &nbsp; Restricted',
		'Eawag' => array( 'link' => $_linkJA20res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA20res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA20res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA20res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA20res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2020-01-01T00:00:00Z TO 2020-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja20',
	),
	'row_pub_ja21' => array(
			'Journal Articles in 2021',
		'Eawag' => array( 'link' => $_linkJA21, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA21, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA21 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA21, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA21 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]',
		),
	),
	'row_pub_ja21oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $_linkJA21oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA21oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA21oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA21oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA21oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja21',
	),
	'row_pub_ja21emb' => array(
			'<!-- no top line -->&nbsp; &nbsp; With embargo',
		'Eawag' => array( 'link' => $_linkJA21emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA21emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA21emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA21emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA21emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja21',
	),
	'row_pub_ja21res' => array(
			'<!-- no top line -->&nbsp; &nbsp; Restricted',
		'Eawag' => array( 'link' => $_linkJA21res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA21res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA21res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA21res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA21res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2021-01-01T00:00:00Z TO 2021-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja21',
	),
	'row_pub_ja22' => array(
			'Journal Articles in 2022',
		'Eawag' => array( 'link' => $_linkJA22, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA22, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA22 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA22, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA22 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]',
		),
	),
	'row_pub_ja22oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $_linkJA22oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA22oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA22oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA22oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA22oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja22',
	),
	'row_pub_ja22emb' => array(
			'<!-- no top line -->&nbsp; &nbsp; With embargo',
		'Eawag' => array( 'link' => $_linkJA22emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA22emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA22emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA22emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA22emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja22',
	),
	'row_pub_ja22res' => array(
			'<!-- no top line -->&nbsp; &nbsp; Restricted',
		'Eawag' => array( 'link' => $_linkJA22res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA22res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA22res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA22res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA22res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2022-01-01T00:00:00Z TO 2022-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja22',
	),
	'row_pub_ja23' => array(
			'Journal Articles in 2023',
		'Eawag' => array( 'link' => $_linkJA23, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA23, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA23 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA23, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA23 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]',
		),
	),
	'row_pub_ja23oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $_linkJA23oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA23oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA23oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA23oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA23oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja23',
	),
	'row_pub_ja23emb' => array(
			'<!-- no top line -->&nbsp; &nbsp; With embargo',
		'Eawag' => array( 'link' => $_linkJA23emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA23emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA23emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA23emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA23emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja23',
	),
	'row_pub_ja23res' => array(
			'<!-- no top line -->&nbsp; &nbsp; Restricted',
		'Eawag' => array( 'link' => $_linkJA23res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA23res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA23res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA23res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA23res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2023-01-01T00:00:00Z TO 2023-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja23',
	),
	'row_pub_ja24' => array(
			'Journal Articles in 2024',
		'Eawag' => array( 'link' => $_linkJA24, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA24, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA24 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA24, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA24 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]',
		),
	),
	'row_pub_ja24oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $_linkJA24oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA24oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA24oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA24oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA24oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja24',
	),
	'row_pub_ja24emb' => array(
			'<!-- no top line -->&nbsp; &nbsp; With embargo',
		'Eawag' => array( 'link' => $_linkJA24emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA24emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA24emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA24emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA24emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja24',
	),
	'row_pub_ja24res' => array(
			'<!-- no top line -->&nbsp; &nbsp; Restricted',
		'Eawag' => array( 'link' => $_linkJA24res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA24res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA24res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA24res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA24res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2024-01-01T00:00:00Z TO 2024-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja24',
	),
	'row_pub_ja25' => array(
			'Journal Articles in 2025',
		'Eawag' => array( 'link' => $_linkJA25, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA25, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA25 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA25, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA25 . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]',
		),
	),
	'row_pub_ja25oa' => array(
			'<!-- no top line -->&nbsp; &nbsp; Open Access',
		'Eawag' => array( 'link' => $_linkJA25oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA25oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA25oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA25oa, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA25oa . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Open%5c%20Access AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja25',
	),
	'row_pub_ja25emb' => array(
			'<!-- no top line -->&nbsp; &nbsp; With embargo',
		'Eawag' => array( 'link' => $_linkJA25emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA25emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA25emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA25emb, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA25emb . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND RELS_EXT_fullText_literal_ms:Restricted%5c%20%5c%28Embargo%5c%29 AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja25',
	),
	'row_pub_ja25res' => array(
			'<!-- no top line -->&nbsp; &nbsp; Restricted',
		'Eawag' => array( 'link' => $_linkJA25res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'Empa' => array( 'link' => $_linkJA25res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'PSI'  => array( 'link' => $_linkJA25res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Apublications', 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:%22info%5C%3Afedora%5C%2Fpsi%5C%3Apublications%22 AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'WSL'  => array( 'link' => $_linkJA25res, 'result' => -1, 'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]' ),
		'_sum'  => array( 'link' => '', 'result' => -1, 'query' => '' ),
		'_PSI' => array(
			'link' => $_linkJA25res . '&f[7]=RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal',
			'result' => -1,
			'query' => $queryPid . ' AND mods_genre_ms:Journal%5c%20Article AND RELS_EXT_isMemberOfCollection_uri_ms:*%5C%3Aexternal AND (RELS_EXT_fullText_literal_ms:Restricted OR RELS_EXT_fullText_literal_ms:No%5c%20Full%5c%20Text) AND mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2025-01-01T00:00:00Z TO 2025-12-31T23:59:59Z]',
		),
		'100%' => 'row_pub_ja25',
	),
);


$tableAry = array_merge( $tableSearchLinks, $tableViewsDLs );

global $user;
global $base_path;
$_inst = trim($base_path,'/');
$_date = ( @intval($_GET['date']) > 2020 && strtotime($_GET['date']) ) ? trim(strip_tags($_GET['date']),'+/|;:,.') : '';	// e.g. ?date=2021-03-29

$userRoles = array_values($user->roles);
if ( in_array('administrator',$userRoles) || in_array('repo manager',$userRoles) || in_array('editor',$userRoles) ) {
	$cacheLifeTime = 5;		// over-ride: accepting (almost) no caching duration for some roles
}

/*
/var/www/html/sites/default/files/dora-page.about-stats/lib4ri.about-stats.cache.2022-11-20.json
https://www.dora.lib4ri.ch/files
https://www.dora.lib4ri.ch/sites/default/files/dora-page.about-stats/lib4ri.about-stats.cache.2022-11-20.json

*/

$cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/sites/' . ( empty($_inst) ? 'default' : $_inst ) . '/files/dora-page.about-stats/';
if ( @!is_dir($cacheDir) ) { mkdir($cacheDir); }
$cacheFile = $cacheDir . ( empty($_inst) ? 'lib4ri' : $_inst ) . '.about-stats.cache.' . ( intval($_date) ? $_date : date("Y-m-d") ) . '.json';

$cacheList = array();
foreach( scandir($cacheDir) as $dirItem ) {
	if ( substr($dirItem,-5) == '.json' ) {
		if ( $date = strtok(substr(strchr($dirItem,'.20'),1),'.') ) { // to cut out e.g. 2021-03-29, if unique id/hash is wanted once, add it AFTER date!
			$cacheList[$date] = $dirItem;
		}
	}
}
// cache file handling:
$cacheAge = empty($_date) ? ( @filesize($cacheFile) ? ( time() - filemtime($cacheFile) ) : time() ) : 1 /* pretend 1 sec with with date preset */;

// cache test:
// echo "<pre>File now: " . $cacheFile . "<br>" . print_r($cacheList,true) . "</pre>"; // return;
// echo $cacheAge . " ::: " . $cacheLifeTime . "<br>"; return;
// cache test:
// echo "<pre>File now: " . $cacheFile . "<br>" . print_r($cacheList,true) . "</pre>"; // return;

if ( $maySelectDate && @in_array('administrator',array_values($user->roles)) ) {
	$readFromCache = true; // must be(come) true
}


if ( $readFromCache && $_delay < 1 && $cacheAge <= $cacheLifeTime ) {
	$tableAry = json_decode( file_get_contents($cacheFile), TRUE );
	if ( @isset($tableAry['_overview']) ) {
		$overviewAry = $tableAry['_overview'];
		unset($tableAry['_overview']);
	}
	if ( $jsAnimated && @in_array('administrator',array_values($user->roles)) ) {
		$jsAnimated = false;
	}
	/*
	foreach( $tableAry as $rowIdx => $rowAry ) {
		if ( !user_is_logged_in() ) {
			if ( substr($rowIdx,0,12) == 'row_pub_ja20' ) { unset($tableAry[$rowIdx]); }
		}
	}
	*/
} else { // search Solr for result numbers (and sum them up):
	foreach( $tableAry as $rowIdx => $rowAry ) {
		if ( substr($rowIdx,0,1) == '_' ) { continue; }
	/*
		if ( !user_is_logged_in() ) {
			if ( substr($rowIdx,0,12) == 'row_pub_ja20' ) { continue; }
		}
	*/
		$sumRow = 0;
		foreach( $rowAry as $cellIdx => $cellData ) {
			if ( is_array($cellData) ) {
				if ( $cellData['result'] >= 0 ) {
					$sumRow += $cellData['result'];
				} elseif ( $cellIdx == '_sum' ) { // add the sum now
					$tableAry[$rowIdx][$cellIdx]['result'] = $sumRow;
				} elseif( !empty($cellData['query']) ) {
					if ( $_delay > 0 ) { usleep( $_delay * 1000 ); }
					$tableAry[$rowIdx][$cellIdx]['result'] = about_page_solr_query($cellData['query'],trim($cellIdx,'_0123456789'));
					$sumRow += $tableAry[$rowIdx][$cellIdx]['result'];
				}
				// replace @inst by institute's name:
				$inst = ( $cellIdx == '_sum' ) ? '*' : strtolower(trim($cellIdx,'_0123456789.')); // = institute
				foreach( array('link','query') as $idx ) {
					while ( $pos = @strpos($tableAry[$rowIdx][$cellIdx][$idx],'@inst') ) {
						$tableAry[$rowIdx][$cellIdx][$idx] = substr($tableAry[$rowIdx][$cellIdx][$idx],0,$pos) . $inst . substr($tableAry[$rowIdx][$cellIdx][$idx],$pos+5);
					}
				}
			}
		}
	}
}

//	echo "<pre>File now: " . $cacheFile . "<br>" . print_r(scandir($cacheDir),true) . "</pre>"; // 
//	echo "<pre>File now: " . $cacheFile . "<br>" . print_r($tableAry,true) . "</pre>"; // 


$introPubAll = $overviewAry['Total Records']['result'];
$introPubFT = $overviewAry['With full text']['result'];
$introPubOA = $overviewAry['Open Access']['result'];

$html = about_page_number_apo($overviewAry['Total Records']['result'],$sepChar);
echo "<br><span style='position:relative; top:-0.5em'>";
echo 'Currently ' . '<span class="aboutDelayed0">' . about_page_delayed($html,$jsAnimated) . '</span>' . ' publications are recorded in DORA, ';
$html = about_page_number_apo($introPubFT,$sepChar) . ' (' . $overviewAry['With full text']['percent'] . '%)';
echo '<span class="aboutDelayed0">' . about_page_delayed($html,$jsAnimated) . '</span>' . ' of which have a full text document available and ';
$html = about_page_number_apo($introPubOA,$sepChar) . ' (' . $overviewAry['Open Access']['percent'] . '%)';
echo '<span class="aboutDelayed0">' . about_page_delayed($html,$jsAnimated) . '</span>' . ' are Open Access.' . "\r\n";
echo "The table below shows some statistics for the DORA repository:</span>\r\n";


$htmlAry = array( /* html code lines */ );
// vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// selection form:
if ( $maySelectDate && @in_array('administrator',array_values($user->roles)) ) {
	$formName = 'dora-page-about-stats-form'; // only used for ids, classes, but NOT from fields
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
	$htmlAry[] = "<div><input type='submit' value='Change Date' name='submit' id='edit-{$formName}-submit' class='{$formName}-submit' style='line-height:0.975em; position:relative; top:-2px;'>";
	foreach( $argAry as $var => $val ) {
		if ( $var != 'date' && !empty($val) ) { $htmlAry[] = "<input type='hidden' name='{$var}' value='{$val}' />"; }
	}
	$htmlAry[] = '<a href="/about-stats-export" style="position:relative; left:20px; top:4px;" title="Download all historical data as CSV"><img src="/eawag/sites/all/themes/libfourri_theme/images/find-css-image.php?cl=secondary-display-csv" style="border:0; height:24px;"></a>';
	$htmlAry[] = "</div></form></div>";
} else {
	$htmlAry[] = ( intval($_date) ? ( 'From: ' . date("M jS, Y",strtotime($_date)) ) : '' ) . '<br>&nbsp;';
}
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


// parse table array as HTML (so far not as CSS):
$delayCount = 0; // optional
echo "<table id='{$tableTheme}' style='position:relative; top:-1em;' cellpadding='5' >\r\n";
foreach( $tableAry as $rowIdx => $rowAry ) {	// rowIdx like 'row_pub_ja20'
	if ( substr($rowIdx,0,1) == '_' ) { continue; }
	if ( substr($rowIdx,0,10) == 'row_pub_ja' && !user_is_logged_in() ) {
		$yearRow = intval(substr($rowIdx,10,2)) + 2000;
		if ( $yearRow <= ( $yearNow - $yearSpan ) ) { continue; }
	}
	$cellData = reset($rowAry);
	echo ( !is_string($cellData) || stripos($cellData,'<!-- no top line -->') !== false ) ? "<tr>\r\n" : "<tr class='trMain'>\r\n";
	$delayCount++; // optional
	foreach( $rowAry as $cellIdx => $cellData ) {
		if ( $cellIdx == '100%' ) { continue; }
		$cellId = $rowIdx . "__cell" . $cellIdx;
		$cellVal = '';
		$cellCss = array();
		$cellClass = '';
		if ( !is_array($cellData) ) {
			$cellVal = ( $rowIdx == 'row_repo' && empty($cellIdx) ) ? str_replace('&nbsp;',implode('',$htmlAry),$cellData) /* to add selection from in 1st cell */ : $cellData;
		} else { // array handling:
			$cellVal = ( $tableAry[$rowIdx][$cellIdx]['result'] < 0 ) ? '&mdash;' : $tableAry[$rowIdx][$cellIdx]['result'];
		}
		$tag = 'td';
		if ( $rowIdx == 'row_repo' ) {
			$tag = 'th';
		} else { 
			$cellCss[] = 'white-space:nowrap';
		}
		$cellCss[] = ( $cellIdx ? 'text-align:right' : 'text-align:left' );
		$percent = '';
		if ( is_array($cellData) && ( $ref100 = @strval($rowAry['100%']) ) ) {
			$fTmp = floatval( $cellData['result'] / max($tableAry[$ref100][$cellIdx]['result'],1) * 100 );
			$percent = number_format($fTmp,1);
			$tableAry[$rowIdx][$cellIdx]['percent'] = $percent;
			$percent = '<br>' . str_replace('%',$percent.'%',$percentFormat);
		}
		$linkAry = empty($cellData['link']) ? array('','') : array('<a href="' . $cellData['link'] . '" target="_blank">','</a>');
		$html = $linkAry[0] . about_page_number_apo($cellVal,$sepChar) . $linkAry[1] . $percent;
		if ( is_array($cellData) && ( @!empty($cellData['query']) || ( $cellIdx == '_sum' && strpos($rowIdx,'_pub_') ) ) ) { // optional
			$cellClass = 'aboutDelayed' . strval( $delayCount );
			$html = about_page_delayed($html,$jsAnimated);
		}
		echo "<{$tag} id='{$cellId}' class='" . trim($cellClass) . "' style='" . implode('; ',$cellCss) . ";'>" . $html . "</{$tag}>";
	}
	echo "</tr>\r\n";
}
echo "</table>\r\n";

echo '&nbsp; ' . 'See also the statistics for the organizational units of ';
echo '<a href="https://www.' . $host . '/eawag/units" target="_blank">Eawag</a>, ';
echo '<a href="https://www.' . $host . '/empa/units" target="_blank">Empa</a>, ';
echo '<a href="https://www.' . $host . '/psi/units" target="_blank">PSI</a> and ';
echo '<a href="https://www.' . $host . '/wsl/units" target="_blank">WSL</a>.' . "<br>";
echo '<a name="noteIcon1">&nbsp;</a> ' . $noteIcon1b . ' ' . $noteText1 . "<br><br>\r\n";

// echo "<br><br><pre style='white-space:nowrap;'>" . str_replace("\n","<br>",print_r($tableAry,1)) . "</pre><br>";

// cache it (again) if/after life-time is over:
if ( empty($_date) && ( $_delay > 0 || $cacheAge > $cacheLifeTime ) ) {
	/* Currently  71 445  publications are recorded in DORA,  65 977 (92.3%)  of which have a full text document available and  30 146 (42.2%) are Open Access.... */
	// add for cached file the special array to keep data used for the page intro/overview above the table
	file_put_contents($cacheFile, json_encode( array_merge( array('_overview' => $overviewAry ), $tableAry ), JSON_PRETTY_PRINT));
	// if ( @isset($tableAry['_overview']) ) { unset($tableAry['_overview']); }
}
?>


<script type="text/javascript"><!--

function addAboutStatsDelayed(pagePart) {
	dynCol = document.getElementsByClassName('aboutDelayed'+pagePart);
	for (var c=0; c<dynCol.length; c++) {
		var pos = dynCol[c].innerHTML.indexOf('<!--') + 4;
		if ( pos >= 4 ) {
			var len = dynCol[c].innerHTML.length - pos - 3;
			dynCol[c].innerHTML = dynCol[c].innerHTML.substr(pos,len);
		}
	}
}

<?php
for ($i=1;$i<sizeof($tableAry);$i++) { 	// -1 due to '_overview'
	// example JS row: setTimeout(function(){ addAboutStatsDelayed(0);}, 500 );
	echo 'setTimeout(function(){ addAboutStatsDelayed(' . ($i-1) . ');}, ' . ($i*60+350) . " );\r\n";
}
?>
//--></script>
