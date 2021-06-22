<?php
$instAry = array(
	'Eawag' => 'eawag',
	'Empa' => 'empa',
	'PSI' => 'psi',
	'WSL' => 'wsl',
/*	'Lib4RI' => '*',	*/
);

$typeAry = array(
	'Journal Article'          => '?f[]=mods_genre_ms:Journal%5c%20Article',
	'Newspaper/Magazine Article' => '?f[]=mods_genre_ms:%22Newspaper%20or%20Magazine%20Article%22',
	'(Edited) Book'            => '?f[]=mods_genre_ms:*Book',
	'Book Chapter'          => '?f[]=mods_genre_ms:Book%5c%20Chapter',
	'Proceedings Paper'  => '?f[]=mods_genre_ms:Proceedings%5c%20Paper',
);


// $queryPid = "( PID:@inst\:1* OR PID:@inst\:2* OR PID:@inst\:3* OR PID:@inst\:4* OR PID:@inst\:5* OR PID:@inst\:6* OR PID:@inst\:7* OR PID:@inst\:8* OR PID:@inst\:9* )";
// better do it like this when asking Solr directly, but not necessary if we are going to use the IslandoraSolrQueryProcessor()
$queryPid = 'PID:@inst\:*';
$queryPid .= ' NOT RELS_EXT_isMemberOfCollection_uri_ms:*deleted';
$queryPid .= ' NOT RELS_EXT_isMemberOfCollection_uri_ms:*staged'; // so far only *essential* for PSI


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
		if ( $linkLabel === '' ) {
			return ( url('islandora/search/',array('absolute'=>TRUE)) . $urlRest );
		}
		return ( '<a href="' . url('islandora/search/',array('absolute'=>TRUE)) . $urlRest . '" target="_blank">' . $linkLabel . '</a>' );
	}
}
if ( @!function_exists('dora_page_pad_item') ) {
	function dora_page_pad_item($term = '',$len = 5) {
		return str_pad($term,$len,' ',STR_PAD_LEFT);
	}
}
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

$pad = '  & ';
echo "<pre>"  . dora_page_pad_item('',27) . '& ' . implode($pad,array_map('dora_page_pad_item',array_keys($instAry))) . '   \\\\';
echo '<br>' . '\\hline' . '<br>';
foreach( $typeAry as $pubType => $pubFacet ) { 
	$numAry = array();
	foreach( $instAry as $iName => $iVal ) { 
		$queryTmp = str_replace('@inst',$iVal,$queryPid);
		$num = dora_page_solr_amount( $queryTmp, dora_page_parse_str($pubFacet) );
		$numAry[] = dora_page_make_url( urlencode($queryTmp).$pubFacet, dora_page_pad_item(strval($num)) );
	}
	echo str_pad($pubType,27,' ') . '& ' . implode($pad,$numAry) . '   \\\\' . "\r\n";
}

echo "</pre><br>\r\n";

?>
