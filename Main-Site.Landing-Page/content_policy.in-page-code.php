<?php
/*
	DORA Content Policy - for Drupal - v04d

	Purpose: Goal of this script here is to extract content of a given html document
	and to insert it into institute-specific document by relacing placeholders with the
	institute name which will be detected by the Drupal URL (or by the 'inst' url argument).

	There is partially a strong optimization for html files produced with Microsoft Word.
	When saving better select *filtered* html to reduce MS-Office related code-overhead.
	So far there is only internal support for 1 image on max, to avoid the requirement of
	the document_name_FILES folder that is created by MS Word to store document images in.

	Layout/CSS issues between original document and CMS are proprietarily tuned currently.
*/

// institute names (assumed to find one in a URL right between 2 slashes):
$instAry = array( 'eawag' => "Eawag", 'empa' => "Empa", 'psi' => "PSI", 'wsl' => "WSL", 'lib4ri' => "Lib4RI" );

$_policy_pdf_name = "DORA_Lib4RI_Content_Policy.pdf";			// optional: Name of the PDF to download (only useful/needed if different)

$_policy_dir = "/var/www/html/data/all/";		// where to find our policy
// - the name must start with a year and have 'content_policy' or 'content-policy' inside.
// - there must be a *.pdf and a *.html version of the policy
// - in front of the file extension you may add (optionally) the institute to be used/shown only there.
// Example: 2019_may_content-policy.PSI.pdf + 2019_may_content-policy.PSI.html


$_inst   = ( @!empty($_GET['inst'] ) ) ? rawurldecode($_GET['inst'] ) : "";			// optional, will be auto-detect otherwise
$_d_css  = ( @!empty($_GET['css']  ) ) ? rawurldecode($_GET['css']  ) : "prop|none|orig";		// the 1st entry will count.
$_d_img  = ( @!empty($_GET['img']  ) ) ? rawurldecode($_GET['img']  ) : "none";
$_i_sub  = ( @!empty($_GET['sub']  ) ) ? rawurldecode($_GET['sub']  ) : "[Eawag / Empa / PSI / WSL]|Eawag|eawag";		// terms to subst $_inst with. Note '|' is encoded as '%7C'
$_d_rep  = ( @!empty($_GET['rep']  ) ) ? rawurldecode($_GET['rep']  ) : "<h2=<h3|<h1=<h2";	// to replace a term basically, example 'OK=okey|Welcome=Hello"
$_d_skip = ( @!empty($_GET['skip'] ) ) ? rawurldecode($_GET['skip'] ) : "";	// all (code) rows containing this will be ignored/removed.

$pdf_link = $_SERVER['REQUEST_URI'];
if ( !strchr($pdf_link,"?dl=pdf") && !strchr($pdf_link,"&dl=pdf") )
{
	$pdf_link = ( strchr(basename($pdf_link),"?") ) ? ( $pdf_link . "&dl=pdf" ) : ( $pdf_link . "?dl=pdf" );
}


// institute specific data (to speed up, could also be caught form Drupal code perhaps...)
$addAry = array();		// this array is not good, just for fast convenience - to be tuned...
$addAry['all'][] = array( "7 May 2019</span>", ".</span><br>Download the DORA Content Policy as <a href='$pdf_link' target='_blank'>PDF</a>.<br>\r\n", 0 );		// for D/L on top, next to the date
// $addAry['all'][] = array( "<!-- hr foot -->", "\r\nDownload <a href='$pdf_link' target='_blank'>DORA Content Policy as PDF</a><br><br>\r\n", 0 );	// for D/L on the page bottom
$addAry['all']['foot'] = "\r\n<hr style='margin-top:12pt;margin-bottom:3.0pt;' size=2 width='100%' align=center><!-- hr foot -->\r\n";
// $addAry['wsl'][] = array( "General guidelines", " <a name='suffix_init_wsl_1'>&nbsp;</a>", 0 );
// $addAry['wsl'][] = array( "luded in DORA.", "<sup><b>{<a href='#suffix_info_wsl_1'>a</a>}</b></sup>", 0 );
// $addAry['wsl']['foot'] = "<sup><b>{<a href='#suffix_init_wsl_1'>a</a>}</b></sup><a name='suffix_info_wsl_1'>&nbsp;</a>Exception: Publications published by WSL.<br>\r\n";
// enabling/uncommenting $addAry['wsl'] would add a WSL specific footnote, but currently not needed.


// -------------------------------------------------------------------------------------------
if ( !function_exists('StrCutFromTo') ) {
	function StrCutFromTo( $str, $cut, $to1, $to2, $afterTo ) {	// to eliminate a portion of a string
		$pos = 0;
		if ( ( $pos = strpos( $str, $cut ) ) === false ) { return $str; }
		$end = strlen($str);
		$to = $to1;
		$toLen = 0;
		if ( !empty($to1) )	{
			$end = strpos( $str, $to1, $pos+strlen($cut) );
			$toLen = ( $afterTo & (1 << 0) ) ? strlen($to1) : 0;	// = 1 || 3
		}
		if ( !empty($to2) )	{
			$tmp = strpos( $str, $to2, $pos+strlen($cut) );
			if ( $tmp < $end ) {
				$end = $tmp;
				$to = $to2;
				$toLen = ( $afterTo & (1 << 1) ) ? strlen($to2) : 0;	// = 2 || 3
			}
		}
		if ( $pos == 0 ) {
			if ( $end == 0 ) {
				return StrCutFromTo( ( ( $cut == $to ) ? $str : "" ), $cut, $to1, $to2, $afterTo );
			}
			return StrCutFromTo( ( substr( $str, $end+$toLen ) ), $cut, $to1, $to2, $afterTo );
		}
		if ( $end == 0 ) {
			return StrCutFromTo( ( substr( $str, $pos ) ), $cut, $to1, $to2, $afterTo );
		}
		return StrCutFromTo( ( substr($str,0,$pos).substr($str,$end+$toLen) ), $cut, $to1, $to2, $afterTo );
	}
}
// -------------------------------------------------------------------------------------------


// Which institute is this? Return one of the Lib4RI institutes $pos in the url (lib4ri as aux):
if ( empty($_inst) || @!isset($instAry[strtolower($_inst)]) )
{
	$tmpAry = explode( "/", ( $_SERVER['REQUEST_URI'] ."/". $_SERVER['SCRIPT_FILENAME'] ."/". @$_SERVER['HTTP_REFERER'] ) . "/lib4ri" );
	foreach( $tmpAry as $_inst )
	{
		if ( strlen($_inst) > 1 && @isset($instAry[strtolower($_inst)]) ) { break; }
	}
}
$_inst = strtolower($_inst);		// ensure lowercase!


// auto-find our policy:
if ( empty($_policy_dir) || @!is_dir($_policy_dir) ) {
	echo "ERROR: Could not find {$_policy_dir} !<br>\r\n";
	return;
}
if ( substr($_policy_dir,-1) != "/" ) { $_policy_dir .= "/"; }
$itemAry = array();
$scanAry = scandir($_policy_dir, 1);
foreach( $scanAry as $item ) {
	$item = trim($item);
	if ( is_file($_policy_dir.$item) && filesize($_policy_dir.$item) && 
		( substr($item,0,3) == "201" || substr($item,0,3) == "202" ) && 
			( strtolower(substr($item,-4)) == ".pdf" || strtolower(substr($item,-5)) == ".html" ) && 
				( stristr($item,"Content_Policy") || stristr($item,"Content-Policy") || stristr($item,"ContentPolicy") ) ) {
		$itemAry[strtolower($item)] = $item;
	}
}
ksort($itemAry);
$itemAry = array_reverse($itemAry);
$docAry = array();
if ( strlen($_inst) > 1 ) {			// look for a institute specific policy:
	foreach( $itemAry as $idx => $item ) {
		$idxHtml = substr($idx,0,-(strlen($_inst)+5)) . "." . strtolower($_inst) . ".html";
		if ( stristr(substr($idx,-(strlen($_inst)+5)),".{$_inst}.pdf") && @isset($itemAry[$idxHtml]) ) {
			$docAry[] = array( 'html' => $itemAry[$idxHtml], 'pdf' => $item );
		}
	}
}
if ( empty($docAry) ) {			// look for the newest policy:
	foreach( $itemAry as $idx => $item ) {
		if ( substr($idx,-4) == ".pdf" && @isset($itemAry[substr($idx,0,-4).".html"]) && 
			@!isset($instAry[strtolower(substr(strrchr(".".substr($idx,0,-4),"."),1))]) ) {
			$docAry[] = array( 'html' => $itemAry[substr($idx,0,-4).".html"], 'pdf' => $item );
		}
	}
}
if ( empty($docAry) ) {
	echo "ERROR: Could not find a pleasant pair of PDF and HTML file for the Content Policy.<br>\r\n";
	return;
}
$itemAry = array_shift($docAry);
$_policy_pdf = $_policy_dir . $itemAry['pdf'];
$_policy_html = $_policy_dir . $itemAry['html'];


if ( @strtolower($_GET['dl']) == "pdf" ) {
	if ( @filesize($_policy_pdf) ) {
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . ( empty($_policy_pdf_name) ? basename($_policy_pdf) : $_policy_pdf_name ) . '"' );
		readfile( $_policy_pdf );
	} else {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	}
	exit;
}

// Check the policy documents:
if ( @!filesize($_policy_html) ) {
	echo "<br>ERROR: Policy document as HTML not found!<br>\r\n";
	exit;		// :=(
}
if ( @!filesize($_policy_pdf) ) {
	echo "<br>ERROR: Policy document as PDF not found!<br>\r\n";
	exit;		// :=(
}


$fp1 = NULL;

// get content out of the given HTML document:
if ( !( $fp1 = @fopen( $_policy_html, "r" ) ) )
{
	echo "<br>ERROR: Policy document could not be opened!<br>\r\n";
	exit;
}

$_d_css = rtrim( array_shift( explode( "|", $_d_css ) ) );
$_d_img = rtrim( $_d_img );
$html_css = "";
$html_body = "";

$loop_task = 3;
while ( !feof($fp1) )
{
	$row = fgets( $fp1, 65535 );
	if ( $loop_task < 4 )
	{
		if ( strchr($row,"<head>" ) ) { $loop_task++; }
	}
	elseif ( $loop_task == 4 )
	{
		if ( empty($html_css) )
		{
			if ( substr($_d_css,0,4) != "orig" ) { $loop_task = 5; }
			elseif ( strchr($row,"<style" ) ) { $html_css .= $row; }
			continue;
		} 
		$html_css .= $row;
		if ( strchr($row,"</style>" ) ) { $loop_task = 5; }
	}
	elseif ( $loop_task == 5 )
	{
		if ( strchr($row,"<body" ) ) { $loop_task = 6; }
	}
	elseif ( $loop_task > 5 )
	{
		if ( strchr($row,"</body" ) ) { break; }
		else { $html_body .= $row; }
	}
}
fclose( $fp1 );


// Trying to polish up MS Word's strange HTML coding (line-breaks, anchor,...),
// a protion of the following code is quite MS Word specific, however this
// should not bother pages converted by other doc-2-html tools, but if needed
// there is also the generator meta tag that will contain 'Microsoft Word'.

// Problem1: speacial windows/Word quotes
$html_body = rawurlencode($html_body);
$html_body = str_replace("%E2%80%9C","\"",str_replace("%E2%80%9D","\"",$html_body));		// apostrophic double-quote to normal double-quotes
$html_body = str_replace("%93","\"",str_replace("%94","\"",$html_body));		// apostrophic double-quote to normal double-quotes
$html_body = str_replace("%C2%AB","\"",str_replace("%C2%BB","\"",$html_body));		// << and >> to normal double-quotes
$html_body = str_replace("%AB","\"",str_replace("%BB","\"",$html_body));		// << and >> to normal double-quotes
$html_body = rawurldecode($html_body);

// Problem2: MS Word may put arbitrarly line-breaks (next to existing spaces) 
// and also additional line-break-related spaces.
$html_body = implode( "[R2N)", explode( "\r\n\r\n", $html_body ) );
$html_body = implode( "[R2N)", explode( "\n\n", $html_body ) );
$html_body = implode( "(r1n]", explode( "\r\n", $html_body ) );
$html_body = implode( "(r1n]", explode( "\n", $html_body ) );
if ( !empty($_d_skip) )
{
	$tmpAry = explode( "[R2N)", $html_body );
	$html_body = "";
	foreach( $tmpAry as $part )
	{
		if ( !strchr($part,$_d_skip) ) { $html_body .= $part . "\r\n\r\n"; }
	}
}
else { $html_body = implode( "\r\n\r\n", explode( "[R2N)", $html_body ) ); }

$rowAry = explode( "(r1n]", $html_body );
$html_body = "";	// reset!
foreach( $rowAry as $row ) { $html_body .= trim($row) . " "; }

// Problem3: pure work-around against MS Word's strange anchor placement - to be tuned...
// if ( @empty($_GET['toc']) ) { $html_body = StrCutFromTo( $html_body, '<a name="_Toc', "</a>", "", 1 ); }
// too cruel... - revised with as soft tweak:
$html_body = str_replace( '<a name="_Toc', '<span></span name="_Toc', $html_body );


// replace generally:
if ( ( $tmpAry = explode('|',$_d_rep) ) && !empty($tmpAry) ) {
	foreach($tmpAry as $rPair) {
		if ( ( $rAry = explode('=',$rPair,2) ) && @isset($rAry[1]) ) {
			$html_body = str_replace( $rAry[0], $rAry[1], $html_body );
		}
	}
}

// look for things to replace with the institute's name:
if ( !empty($_i_sub) )
{
	$tmpAry = explode( "|", $_i_sub );
	foreach( $tmpAry as $part ) {
		if ( $part == strtolower($part) || strchr($part,strtolower($instAry[$_inst])) ) {
			$html_body = str_replace( $part, strtolower($instAry[$_inst]), $html_body );
		}
		else { $html_body = str_replace( $part, $instAry[$_inst], $html_body ); }
	}
}

// replace generally (repeat):
if ( ( $tmpAry = explode('|',$_d_rep) ) && !empty($tmpAry) ) {
	foreach($tmpAry as $rPair) {
		if ( ( $rAry = explode('=',$rPair,2) ) && @isset($rAry[1]) ) {
			$html_body = str_replace( $rAry[0], $rAry[1], $html_body );
		}
	}
}


// remove notes not matching to this institute:
foreach( $instAry as $iIdx => $iTmp )
{
	if ( $iIdx != $_inst )
	{
		$rowAry = explode( "\n", $html_body );
		$html_body = "";	// reset!
		foreach( $rowAry as $row )		// looking for e.g. suffix_init_wsl_1 / suffix_info_wsl_1
		{
			if ( ( $pos = strpos($row,"#suffix_i") ) && stristr(substr($row,$pos+11,11),$iIdx) )			// approx cut
			{
				$row = ( stristr(substr($row,$pos,13),"#suffix_init_") ) ? "" : StrCutFromTo( $row, "<sup>", "</sup>", "", 1 );
			}
			$html_body .= $row . "\n";
		}
	}
}

// remove images if wanted
if ( substr($_d_img,0,4) == "none" ) { $html_body = StrCutFromTo( $html_body, "<img ", ">", "", 1 ); }

// layout/CSS related tunings:
if ( substr($_d_css,0,4) != "orig" )
{
	// try to remove any style specs from inside html tags - to be tuned...
	$html_body = str_replace( "class=MsoNormal", "", $html_body );
	$html_body = StrCutFromTo( $html_body, "style='font", "'", "", 1 );
	$html_body = StrCutFromTo( $html_body, "style='position", "'", "", 1 );
	$html_body = str_replace( "  ", " ", $html_body );
}


$html_body = str_replace( "<p style='margin-bottom:3.0pt'><span lang=EN-GB>&nbsp;</span></p>", "&nbsp;<br>", $html_body );
$html_body = str_replace( "&nbsp;&nbsp;&nbsp;", "\t", $html_body );


// Problem4: Institute specific tunings, just put here to save time right now - to be tuned...
if ( @isset($addAry['all']) )			// replacement
{
	if ( @isset($addAry['all']['foot']) )		// addition (bottom)
	{
		$html_body .= $addAry['all']['foot'];
	}
	foreach( $addAry['all'] as $tmpAry )
	{
		if ( is_array($tmpAry) )
		{
			$rep_flip = @intval( $tmpAry[2] );
			$html_body = str_replace( $tmpAry[0], $tmpAry[$rep_flip].$tmpAry[1-$rep_flip], $html_body );
		}
	}
}
if ( @isset($addAry[$_inst]) )			// replacement
{
	if ( @isset($addAry[$_inst]['foot']) )		// addition (bottom)
	{
		$html_body .= $addAry[$_inst]['foot'];
	}
	foreach( $addAry[$_inst] as $tmpAry )
	{
		if ( is_array($tmpAry) )
		{
			$rep_flip = @intval( $tmpAry[2] );
			$html_body = str_replace( $tmpAry[0], $tmpAry[$rep_flip].$tmpAry[1-$rep_flip], $html_body );
		}
	}
}


$html_body .= "<br>&nbsp;<br>\r\n";


if ( substr($_d_css,0,4) == "prop" ) {
	// CSS override if wanted:
	$html_css = "\r\n<!-- PROPRIETARY CSS, JUST FOR INSERTED CONTENT BELOW -->\r\n<style><!--\r\n";
	$html_css .= " h2 { font-size:13.0pt; font-weight:bold; }\r\n";
	$html_css .= " h3 { font-size:12.0pt; font-weight:bold; }\r\n";
	$html_css .= " ol { margin-top:0cm; margin-bottom:0cm; }\r\n";
	$html_css .= " ul { margin-top:0cm; margin-bottom:0cm; }\r\n";
	$html_css .= "--></style>\r\n";
	// would also work (here):
	// $html_body = str_replace( "<h1 style='", "<h1 style='font-size:13.0pt;font-weight:bold;", $html_body );
}


// final output into the Drupal page:
echo $html_css;		// may be empty
echo "\r\n\r\n<!-- INSERTING CONTENT, START -->\r\n"; 
echo "\r\n\r\n" . $html_body . "\r\n";
echo "\r\n\r\n<!-- INSERTING CONTENT, END -->\r\n\r\n\r\n";

?>