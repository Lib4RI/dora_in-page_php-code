<p>
 <a href='/' target='_blank'>DORA</a> is the institutional repository and bibliography for all research articles and other publications affiliated 
with the four research institutes within the ETH Domain (<a href='https://www.eawag.ch/' target='_blank'>Eawag</a>, <a href='https://www.empa.ch/' target='_blank'>Empa</a>, <a href='https://www.wsl.ch/' target='_blank'>WSL</a> and <a href='https://www.psi.ch/' target='_blank'>PSI</a>) and hosted by the <a href='https://www.lib4ri.ch/' target='_blank'>Lib4RI</a>. DORA is based on the open source software framework <a href='https://islandora.ca/' target='_blank'>Islandora</a>, which uses Drupal, Fedora and SOLR as components. As a service to our users we developed an <a href='https://www.lib4ri.ch/files/poster_openrepos2019.pdf' target='_blank'>ingestion workflow</a>, which you can find freely available on <a href='https://github.com/Lib4RI/pub_db_lib' target='_blank'>GitHub</a>. 
<br>

<br>DORA acts simultaneously as:
<ul style='margin-bottom:0px; position:relative; top:-1em;'><span>
	<li><b>Bibliography</b> &mdash; DORA records the scientific publications produced at the research institutes 
		and is a source for publication lists on the institutional websites and for academic reports</li>
	<li><b>Archive</b> &mdash; DORA preserves the full text of the institutes publications 
		and makes them freely available to all internal members</li>
	<li><b>Open Access (OA) Repository</b> &mdash; Researchers are able to make a full text version 
		of their scientific articles freely available in DORA (green road to OA), 
		thus facilitating compliance with the OA policies of many research funders</li>
</ul>

Currently <span id='maxPubAll'>66855</span> publications are recorded in DORA, 
<span id='maxPubFT'>60246</span> of which have a full-text document available and 
<span id='maxPubOA'>23985</span> are open access.<br>

<br>



	
<script type="text/javascript"><!--

var imgTag = "<img src='https://upload.wikimedia.org/wikipedia/commons/d/de/Ajax-loader.gif' style='width:15px; height:15px; position:relative; top:1px' />";
var pubAreaAll = document.getElementById('maxPubAll');
if ( pubAreaAll != null ) { pubAreaAll.innerHTML = 'many' + imgTag; }
var pubAreaOA = document.getElementById('maxPubOA');
if ( pubAreaOA != null ) { pubAreaOA.innerHTML = 'many' + imgTag; }
var pubAreaFT = document.getElementById('maxPubFT');
if ( pubAreaFT != null ) { pubAreaFT.innerHTML = 'many' + imgTag; }

//--></script>




<?php
echo "<sc"."ript type='text/ja"."vasc"."ript'><!--\r\n";

$maxAll = 0;
foreach( array('eawag','empa','psi','wsl') as $inst ) {
	$solr = new IslandoraSolrQueryProcessor();
	$solr->buildQuery( "PID:{$inst}\:*" );
	$solr->solrLimit = 1;
	$solr->executeQuery(FALSE);
	$maxAll += @intval( $solr->islandoraSolrResult['response']['numFound'] );
}
echo "setTimeout(function(){\r\n";
echo "\tvar pubAreaAll = document.getElementById('maxPubAll');\r\n";
echo "\tif ( pubAreaAll != null ) { pubAreaAll.innerHTML = '" . $maxAll . "'; }\r\n";
echo "}, 750 );\r\n";


$solr = new IslandoraSolrQueryProcessor();
$solr->buildQuery( "RELS_EXT_fullText_literal_ms:No%5C%20Full%5C%20Text" );
$solr->solrLimit = 1;
$solr->executeQuery(FALSE);
$maxFT = $maxAll - @intval( $solr->islandoraSolrResult['response']['numFound'] );

$nTmp = $maxFT . ' (' . number_format( floatval( $maxFT / max($maxAll,1) * 100 ), 1 ) . '%)';
echo "setTimeout(function(){\r\n";
echo "\tvar pubAreaFT = document.getElementById('maxPubFT');\r\n";
echo "\tif ( pubAreaFT != null ) { pubAreaFT.innerHTML = '" . $nTmp . "'; }\r\n";
echo "}, 1200 );\r\n";


$solr = new IslandoraSolrQueryProcessor();
$solr->buildQuery( "RELS_EXT_fullText_literal_ms:Open%5c%20Acc*" );
$solr->solrLimit = 1;
$solr->executeQuery(FALSE);
$maxOA = @intval( $solr->islandoraSolrResult['response']['numFound'] );

$nTmp = $maxOA . ' (' . number_format( floatval( $maxOA / max($maxAll,1) * 100 ), 1 ) . '%)';
echo "setTimeout(function(){\r\n";
echo "\tvar pubAreaOA = document.getElementById('maxPubOA');\r\n";
echo "\tif ( pubAreaOA != null ) { pubAreaOA.innerHTML = '" . $nTmp . "'; }\r\n";
echo "}, 1500 );\r\n";

echo "//--></sc"."ript>\r\n";
?>
