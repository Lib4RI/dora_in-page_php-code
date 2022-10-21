<div style="margin:1ex 2ex -1ex 2ex;">

	<h2 class="block-title">Open for Climate Justice - Open Access Week 2022</h2>

	Following <a href="https://www.openaccessweek.org/">International Open Access</a> week's theme "Open for Climate Justice" we identified recent
	<?php $inst = trim($GLOBALS['base_path'],'/'); echo ( empty($inst) ? 'Lib4RI' : ( strlen($inst) > 3 ? ucfirst($inst) : strtoupper($inst) ) ); ?> journal articles related to climate change. 
	To increase the visibility of these articles, we asked the authors for the accepted version of non-Open-Access articles, as most publishers permit that
	accepted versions are published Open Access in institutional repositories (<a href="https://www.lib4ri.ch/news.html++/year/2022/item/329/">see related news</a>). 
	From the many <a href=https://www.dora.lib4ri.ch<?php echo $GLOBALS['base_path']; ?>islandora/search/mods_note_notes_lib4ri_mt%3A%23climateAction>publications 
	related to climate change</a>, you can find a short selection below.

</div>

<?php

$slideDelay = 10000;		// in MilliSeconds
$citStyle = 'APA';

$searchFor = 'mods_abstract_mt:*+AND+(dc.title:Climat*+OR+mods_abstract_mt:Climat*)'; // only used on DEV!
$pidList = '/var/www/html/data/all/WIKI.Publications+for+HomePage.txt';  // only used on PROD!

module_load_include('module', 'citeproc', 'citeproc');
module_load_include('inc', 'lib4ridora', 'includes/utilities');
module_load_include('inc', 'lib4ridora', 'theme/theme');

global $base_path;
$instName = trim($base_path,'/');
$htmlCode = '';
$htmlCache = '/tmp/dora.cache.slideshow-oa.' . ( empty($instName) ? 'default' : $instName ) . '.html';
$cachePIN = 0 - @intval( variable_get('dora_slideshow_cache_pin',0) );	// force to update cache only, no real page creation (for Crontab!?)


$cacheDur = ( @isset($_GET['cache']) ? @intval($_GET['cache']) : 43200 );	// default cache lifetime (aside from forced cache update)
$cacheSize = @filesize($htmlCache);
$cacheAge = time() - @filemtime($htmlCache);

if ( $cacheAge < 60 ) { // for safety, to avoid too frequent re-caching, try to access the file which ever it is.
	$htmlCode = @file_get_contents($htmlCache);
} elseif ( $cacheSize > 100 && $cacheAge < $cacheDur /* regular cache handling */ && ( $cachePIN == 0 || $cacheDur != $cachePIN ) /* force caching only */ ) {
	$htmlCode = file_get_contents($htmlCache);
} else {
	$pidAry = [];
	$solrHost = ( @sizeof($_SERVER) ? $_SERVER['HTTP_HOST'] : 'lib-dora-dev1.emp-eaw.ch' );
	if ( strpos($solrHost,'prod1') ) {
		$rowAry = @file($pidList);
		if ( sizeof($rowAry) < 3 ) {
			echo 'Could not identify sufficient publications to show!<br>';
			if ( user_is_logged_in() ) { echo "Expected file '" . basename($pidList) . "' does not seem to contain PIDs!?<br>"; }
			return; // end here!
		}
		foreach( $rowAry as $pidRow ) {
			if ( substr(ltrim($pidRow),0,1) == '#' /* commented out */ ) { continue; }
			$instAry = ( empty($instName) ? array('eawag','empa','psi','wsl') : array($instName) );
			foreach( $instAry as $inst ) {
				$tmpAry = explode($inst.':',$pidRow);
				foreach( $tmpAry as $idx => $tmp ) {
					if ( intval($idx) && @substr(rtrim($tmpAry[$idx-1]),-1) != '#' /* commented out */ ) {
						if ( $num = intval($tmp) ) {
							$pid = $inst.':'.$num;
							$pidAry[$pid] = $pid;
						}
					}
				}
			}
		}
	} else { // on DEV:
		include_once("/var/www/html/sites/all/modules/lib4ri_author_update/includes/utilities.inc");
		$solrUrl = 'http://' . $solrHost . ':8080/solr/collection1/select?q=PID:' . ( empty($instName) ? '*' : $instName ) . '%5c%3a*+AND+' . $searchFor . '+AND+RELS_EXT_fullText_literal_ms:Open%5c%20Access+AND+RELS_INT_lib4ridora-multi-embargo-availability_literal_ms:public+AND+(RELS_INT_lib4ridora-multi-embargo-document_version_literal_ms:publish*+OR+RELS_INT_lib4ridora-multi-embargo-document_version_literal_ms:accept*)+AND+(fedora_datastream_info_PDF_ID_mt:*%20OR%20fedora_datastream_info_PDF2_ID_mt:*%20OR%20fedora_datastream_info_PDF3_ID_mt:*)&indent=true&wt=csv&csv.separator=%7c&sort=PID+asc&rows=987654321&fl=PID%2c+fgs_createdDate_dt%2c+dc.title%2c+mods_abstract_mt';
		$doraAry = lib4ri_author_update_csv_to_array($solrUrl, '|', /* 'fgs_createdDate_dt' */ 'PID' );
		$pidAry = array_combine( array_keys($doraAry), array_keys($doraAry) );
		ksort($pidAry);
	}

	$styleAry = citeproc_style( variable_get( 'lib4ri_citation_export_style', CSL::GetDefaultName() ) );
	if ( empty($styleAry) ) { $styleAry = citeproc_default_style(); }
	if ( sizeof($styleAry) && !empty($citStyle) ) { $styleAry['name'] = $citStyle; }

	// Turn PID list into a corrsponding set of publications, each with requested metadata as html:
	$pubAry = [];
	foreach( $pidAry as $pid ) {
		if ( $obj = islandora_object_load($pid) ) {
			if ( $mods = $obj['MODS']->content ) {
				$citation = citeproc_bibliography_from_mods($styleAry, $mods);	// will return a string, see: /var/www/html/DORA/sites/all/modules/islandora_scholar/modules/citeproc/citeproc.module
				$citation = preg_replace('/\s+/',' ',strtr($citation,"\r\n\t","   "));
				$pubAry[$pid]['cit'] = trim($citation);
			}
			$pdfOA = lib4ridora_get_open_access_pdf($obj);
			if ( empty($pdfOA['dsid']) ) {
				$pubAry[$pid]['pdf'] = '&nbsp;';
			} else {
				$pdfName = lib4ridora_download_name_pdf($pid,'pdf',$pdfOA['document_version'],$pdfOA['dsid']);
				$urlFull = ( !variable_get('lib4ri_sitemap_semicolon_decoded',FALSE) ? $pid : str_ireplace('%3A',':',$pid) );
				$urlFull = strtok($pid,':') . '/islandora/object/' . $urlFull . '/datastream/' . $pdfOA['dsid'];
				$urlFull = url( $urlFull.'/'.$pdfName, array('absolute' => true, 'https' => true) ); // as on the 'Detailed Record' page
				$pubAry[$pid]['pdf'] = '<span class="fa fa-unlock-alt" style="margin-right:4px; color:#090;"></span><a href="' . $urlFull . '">' . ucwords($pdfOA['document_version']) . '</a>';
			}
			$pubAry[$pid]['url'] = '<a href="https://www.dora.lib4ri.ch/' . $instName . '/islandora/object/' . $pid . '" target="_blank">Detailed Record <b>&raquo;</b></a>';
		}
	}

	// Creating DIV-areas for each publication:
	$pubCount = 0;
	foreach( $pubAry as $pid => $pidAry ) {
		$htmlAry = array(
			'<div class="crsl-item"><div style="background-color:' . ( ( $pubCount % 2 ) ? '#e0f0e8;' : '#e0e8f0;' ) . '; padding: 5px 5px 5px 5px;">',
			'<!-- T/N area go! --><div class="thumbnail"><img height="400" width="300" style="height:400px; width:300px;" src="https://www.dora.lib4ri.ch/' . $instName . '/islandora/object/' . $pid . '/datastream/PREVIEW/view">',
				'<div style="display:inline-block; position:relative; left:0.5em;">',
					'<div class="postdate" style="display:none">2345-01-23</div>',
					'<div class="lib4ridora-pdf-link">' . $pidAry['pdf'] . '</div>',
					'<div class="" style="height:300px;">&nbsp;</div>',
					'<div class="readmore">' . $pidAry['url'] . '</div>',
					'<div class="" style="display:auto">&nbsp;</div>',
				'</div>',
			'</div><!-- T/N area end -->',
			'<h3 style="size:12pt !important; display:none;">Publication Title</b></h3>',
			'<span style="size:12pt; font-weight:500;">' . $pidAry['cit'] . '</span>',
			'</div></div>',
		);
		$htmlCode .= implode("\r\n", $htmlAry) . "\r\n";
		$pubCount++;
	}
	
	file_put_contents( $htmlCache, $htmlCode );	// cache it!
	
	if ( $cacheDur == $cachePIN ) {
		// header('HTTP/1.1 201 Created'); // we already have text flushed...
		die('<ul><br>DORA Open Access SlideShow Cache created!</br></ul>');
		exit;
	}
}
?>


<script type="text/javascript">

// correct postion of block title:
var titAry = document.querySelectorAll('h2.block-title');
for( t in titAry ) {
	if ( String(titAry[t].innerText).indexOf('Climate Justice') >= 0 ) {
		titAry[t].style = 'text-align:center; position:relative; top:1ex;'; break;
	}
}

</script>
<!-- https://monsterspost.com/coding-responsive-horizontal-posts-slider-using-css3-jquery/ -->
<!-- https://github.com/basilio/responsiveCarousel -->
<script type="text/javascript" src="https://www.dora.lib4ri.ch<?php echo $GLOBALS['base_path']; ?>data/all/js/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="https://www.dora.lib4ri.ch<?php echo $GLOBALS['base_path']; ?>data/all/js/responsiveCarousel.min.js"></script>


<div id="w" style="padding: 0 0.5em 0 1em; position:relative; top:0.75em; background-color:#eCf4f0;">

    <!-- h1>Horizontal Posts Slider with jQuery (<a href="https://github.com/basilio/responsiveCarousel" target="_blank">'responsiveCarousel'</a>)<br>&nbsp;</h1 -->

    <nav class="slidernav">
      <div id="navbtns" class="clearfix" style="display: inline-block; width:100%;">
	  <table style="width:108%; position:relative; position:relative; left:-4.5%; top:16em; margin-top:-1em; margin-bottom:-3em;"><tr><td align="left">
		<div style="display:inline-block; align:left;">
			<a href="#" class="previous" id="slideshow-link-prev"><a href="#" style="font-size:40pt; font-weight:700;" onclick="clearInterval(slideshow_looping);document.getElementById('slideshow-link-prev').click();">&laquo;</a></a>
		</div>
	</td><td align="right">
        <div style="display:inline-block; align:right;">
			<a href="#" class="next" id="slideshow-link-next"><a href="#" style="font-size:40pt; font-weight:700;" onclick="clearInterval(slideshow_looping);document.getElementById('slideshow-link-next').click();">&raquo;</a></a>
		</div>
		</td></tr></table>
      </div>
    </nav>

    
    <div class="crsl-items" data-navigation="navbtns">
		<div class="crsl-wrap">

		<?php echo $htmlCode; ?>

      </div><!-- @end .crsl-wrap -->
    </div><!-- @end .crsl-items -->

  </div><!-- @end #w -->

<script type="text/javascript">

// auto-loop!
const slideshow_looping = setInterval( function() { if ( elem = document.getElementById('slideshow-link-next') ) { elem.click(); } } , <?php echo max($slideDelay,500); ?> );

// basics:
$(function(){
  $('.crsl-items').carousel({
    visible: 3,
    itemMinWidth: 180,
    itemEqualHeight: 370,
    itemMargin: 9,
  });
  
  $("a[href=#]").on('click', function(e) {
    e.preventDefault();
  });
});

</script>
