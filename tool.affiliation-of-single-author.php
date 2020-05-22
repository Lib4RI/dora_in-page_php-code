<?php


$myName = "Frank";
if ( !user_is_logged_in() ) {
	if ( $_SERVER['HTTP_USER_AGENT'] != "Mozilla/5.0 (Lib4RI/Frank/AffiliaTest)" ) { echo "This is a 'working' page.<br>Please contact {$myName} for details.<br><br>"; return; }
} else {
	global $user;
	if ( !empty($myName) && @!empty($_SERVER['HTTP_HOST']) && @strval($user->name) != $myName ) { echo "This is a 'working' page.<br>Please contact {$myName} for details.<br><br>"; return; }
}


	$pid = @rawurldecode($_GET['pid']);
	$ignLimit = ( @!empty($_GET['ign']) ? intval($_GET['ign']) : 50 );
	$showLimit = ( @!empty($_GET['max']) ? intval($_GET['max']) : 10 );
	$showAffSum = @isset($_GET['aff']);
	$createDC   = ( @isset($_GET['dc']) ? intval($_GET['dc']) : -1 );
	$createMODS = ( @isset($_GET['mods']) ? intval($_GET['mods']) : -1 );
	$addAutLast = ( @!isset($_GET['last']) || !empty($_GET['last']) );		// true for adding the last author too
	$setAutList = ( @!isset($_GET['list']) || !empty($_GET['list']) );		// true for an extra list with all authors
	$sepAutList = "|";
	$userMsg = "";

//	if ( $addAutLast ) { $showLimit -= 1; }

	if ( !user_is_logged_in() && $_SERVER['HTTP_USER_AGENT'] != "Mozilla/5.0 (Lib4RI/Frank/AffiliaTest)" ) {
		if ( $showAffSum ) { die("-9"); }
		echo "<br><i>You need to have a login.</i><br><br>";
		return;
	}


	if ( !preg_match( '/^\w+:{1}\d+$/', $pid ) ) {
		if ( $showAffSum ) { die("-5"); }
		echo "<br>This is a simple temporary test/work page for users only who are logged in<br>about the PSI-affiliated authors of a publication.<br>";
		echo "<br>To run it, please expand the URL with a PID, for example with <i style='color:purple'>?pid=psi:12345</i><br><br>";
	//	echo "You also may add <i style='color:purple'>&max=5</i> (default {$extLimit}) &nbsp;to limit the number of non-affiliated authors.<br><br>";
	//	echo "A new MODS XML will be returned then, press Ctrl+U to see it some better.<br><br>";
		return;
	}
	if ( !( $object = islandora_object_load($pid) ) ) {
		if ( $showAffSum ) { die("-1"); }
		echo "<br>Error: Unable to load {$pid} !<br>";
		return;
	}

	if ( !($modsOrig = $object['MODS']->content ) ) { return; }

	
	$autAllCode = array();
	$autListNew = array();
	$autAffiliated = array();
	$autLastCache = array();
	$listOrig = array();	// will hold name strings for originalAuthorList
	$modsRest = "";
	$hasNameEtAl = false;
	$hasOrigAutList = false;

	$domOrig = new DOMDocument();
	$domOrig->loadXML( str_replace("&apos;","+@p0z;",$modsOrig) );		// optional(?) work-around not to get &apos; decoded into a single-quote by xml functions.
	$xpath = new DOMXPath($domOrig);
	$xpath->registerNamespace('mods', 'http://www.loc.gov/mods/v3');

	if ( $elements = $xpath->query('//mods:mods/mods:*') ) {
		$domRest = new DOMDocument;
		$domRest->formatOutput = true;
		$domRest->loadXML('<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink"></mods>');
		foreach( $elements as $element ) {
			if ( !$hasNameEtAl && $element->nodeName == "name" && $element->hasChildNodes() ) {
				foreach($element->childNodes as $nameChild ) {
					if ( $nameChild->nodeName == "etal" ) {	$hasNameEtAl = true; break; }
				}
			}
			if ( !$hasOrigAutList && $element->nodeName == "extension" && $element->hasChildNodes() ) {
				foreach($element->childNodes as $extChild ) {
					if ( $extChild->nodeName == "originalAuthorList" && !empty(rtrim($extChild->nodeValue)) ) { $hasOrigAutList = true; break; }
				}
			}
			$role_term = ( $element->nodeName != "name" ) ? "" : $xpath->evaluate('normalize-space(mods:role/mods:roleTerm[@authority="marcrelator"]/text())', $element);
			$pers_fam = ( $element->nodeName != "name" ) ? "" : $xpath->evaluate('normalize-space(mods:namePart[@type="family"]/text())', $element);
			if ( $role_term != "author" || empty($pers_fam) ) {
				$node = $domRest->importNode($element, true);			// importing node incl. children
				$node = $domRest->documentElement->appendChild($node);		// finally appending it into the "<temp>" node
				continue;
			}
			if ( empty($autAllCode) ) {	// let's add a text maker there to insert the selected name elements.
				$posAux = $domRest->createElement('authorsHere','!');		// to get: <authorsHere>!</authorsHere>
				$domRest->documentElement->appendChild($posAux);
			}
			$pers_giv = $xpath->evaluate('normalize-space(mods:namePart[@type="given"]/text())', $element);
			$xPathTmp = ( stripos($pid,"psi:") === 0 ) ? 'mods:affiliation[@type="group"]' : 'mods:nameIdentifier[@type="organizational unit id"]';
			$pers_unit = trim( $xpath->evaluate('normalize-space('.$xPathTmp.'/text())', $element) );
		//	if ( empty($pers_unit) ) { $pers_unit = trim( $xpath->evaluate('normalize-space(mods:nameIdentifier[@type="authorId"]/text())', $element) ); }
		// working basically, but according to Jochen (2019-11-19) drop authors with an author-id who have no unit/group id assigned.
		
			$domPers = new DOMDocument;
			$domPers->formatOutput = true;
			$domPers->loadXML('<temp xmlns="http://www.loc.gov/mods/v3"><cutHere>!</cutHere></temp>');		// adding some markup plus a text marker
			$node = $domPers->importNode($element, true);			// importing node incl. children
			$domPers->documentElement->appendChild($node);		// finally appending it into the "<temp>" node
			$nameArea = rtrim(substr(strchr(rtrim( $domPers->saveXML() ),"<cutHere>!</cutHere>"),20,-7));		// now as text, 20: <cutHere>!</cutHere>, 7: </temp>
			$autAllCode[] = $nameArea;

			if ( !empty($pers_unit) ) {
				$autAffiliated[] = array( $nameArea, "{$pers_fam}, {$pers_giv}" );
			}
			
			if ( sizeof($autListNew) < $showLimit ) {
				$autListNew[] = array( $nameArea, "{$pers_fam}, {$pers_giv}" );
			} elseif ( !empty($pers_unit) ) {
				$autListNew[] = array( $nameArea, "{$pers_fam}, {$pers_giv}" );
				$autLastCache = array();
			} else {
				$autLastCache = array( $nameArea, "{$pers_fam}, {$pers_giv}" );
			}
			$listOrig[] = $pers_fam . ", " . $pers_giv;
		}
		if ( $addAutLast && sizeof($autLastCache) ) { $autListNew[] = $autLastCache; }

		$modsRest = $domRest->saveXML();
	}


	if ( $showAffSum ) { 		// special (test) output just showing the number of affiliated authors
		echo sizeof($autAffiliated);
		exit;
	}


	// for MODS + DC:
	if ( $setAutList && sizeof($listOrig) && sizeof($autAllCode) > $ignLimit ) {

		if ( $createMODS || $createDC ) {
			// secure orig. datatreams/files:
			$dirSafe = "/tmp/50-Author-Tuning";
			if ( @!is_dir($dirSafe) ) { mkdir($dirSafe); }
			$dirSafe .= "/" . date("Y-m-d.H-i-s");
			if ( @!is_dir($dirSafe) ) { mkdir($dirSafe); }
			$fileSafeBase = $dirSafe . "/" . strtr($pid,":","-");
			if ( @isset($object['PDF']) ) { $object['PDF']->getContent($fileSafeBase.".PDF.pdf"); }
			if ( @isset($object['PDF2']) ) { $object['PDF2']->getContent($fileSafeBase.".PDF2.pdf"); }
			if ( @isset($object['RELS-INT']) ) { $object['RELS-INT']->getContent($fileSafeBase.".RELS-INT.xml"); }
			if ( @isset($object['RELS-EXT']) ) { $object['RELS-EXT']->getContent($fileSafeBase.".RELS-EXT.xml"); }
			if ( @isset($object['DC']) ) { $object['DC']->getContent($fileSafeBase.".DC.xml"); }
			$object['MODS']->getContent($fileSafeBase.".MODS.xml");
		}

		// ====================================== UPDATE MODS ========================================
		if ( $createMODS >= 0 ) {
	
			// add/update the originalAuthorList:
			if ( !$hasOrigAutList && ( $mods = simplexml_load_string($modsRest) ) ) {
				$mods->extension->originalAuthorList = implode($sepAutList,$listOrig);
				$modsRest = $mods->asXML();
				// sadly there may be now minor layout problems, let's make it pretty again:
				$modsRest = str_replace("></extension",">\n  </extension", $modsRest );
				$modsRest = str_replace(">\n  <originalAuthorList",">\n    <originalAuthorList", $modsRest );
			}

			$nameArea = "";
			foreach( $autListNew as $nAry ) { $nameArea .= $nAry[0]; }
			if ( !$hasNameEtAl ) { $nameArea .= "\n  <name>\n    <etal/>\n  </name>"; }

			$modsNew = str_replace("<authorsHere>!</authorsHere>",$nameArea,$modsRest);

			$modsNew = str_replace("+@p0z;","&apos;", $modsNew );			// revert the 'single-quote' work-around

			$tmpAry = explode("\n",$modsNew);		// optional/minor clean-up of empty rows.
			$modsNew = "";
			foreach($tmpAry as $row) { 
				if ( !empty(rtrim($row)) ) { $modsNew .= $row . "\n"; }
			}

			if ( !$createMODS ) {
				echo $modsNew;
				exit;
			} elseif ( strlen($modsNew) != strlen($modsOrig) || $modsNew != $modsOrig ) {
				$object['MODS']->content = $modsNew;
				$userMsg .= "<br> {$pid} : MODS datasream created! <br>";
			} else {
				$userMsg .= "<br> {$pid} : MODS datastream already up to date, so nothing done! <br>";
			}
		}


		// ======================================= UPDATE DC =========================================
		if ( $createDC >= 0 ) {									// ..working but ugly!

			if ( $dcCode = @$object['DC']->content ) {

				$dcAry = explode("<dc:creator",$dcCode);			// note: dc.creator may have an attribute sometimes: xmlns:dc="http://purl.org/dc/elements/1.1/"
				$dcRest = array_shift($dcAry);
				$dcAtt = "";	// if there are attributes for dc:creator then just get it once for all.
				foreach($dcAry as $dcIdx => $dcPart ) {
					if ( $pos = strpos($dcPart,"</dc:creator>") ) {
						if ( substr($dcPart,$pos-1,1) != ")" || substr($dcPart,$pos-8,8) == "(author)" ) {
							if ( $dcIdx < 2 && empty($dcAtt) ) {
								$dcAtt = substr(strtok(" ".$dcPart,">"),1);
							}
							$dcRest .= rtrim(substr($dcPart,$pos+13),"\r\n");
							continue;
						}
					}
					$dcRest .= "<dc:creator" . $dcPart;
				}

				$dcAry = array();
				foreach( $autListNew as $nAry ) { 
					$dcAry[] = "<dc:creator" . $dcAtt . ">" . $nAry[1] . " (author)</dc:creator>";
				}

				$dcNew = "";
				if ( $pos = strpos($dcRest,"</dc:title>") ) {
					$dcNew = substr($dcRest,0,$pos+11) . "\n  " . implode("\n  ",$dcAry) . substr($dcRest,$pos+11);
				} elseif ( $pos = strpos($dcRest,"</oai_dc:dc>") ) {
					$dcNew = substr($dcRest,0,$pos) . "  " . implode("\n  ",$dcAry) . "\n" . substr($dcRest,$pos);
				}

				$dcAry = explode("\n",$dcNew);		// optional/minor clean-up of empty rows.
				$dcNew = "";
				foreach($dcAry as $row) { 		// remove rows like "<dc:creator> (editor)</dc:creator>" or empty rows generally
					if ( !strpos($row,"/>") && strpos($row,"(") && empty(rtrim(strip_tags(strtr($row,"()","<>")))) ) { continue; }
					if ( !empty(rtrim($row)) ) { $dcNew .= $row . "\n"; }
				}

				if ( !$createDC ) {
					echo $dcNew;
					exit;
				} elseif ( strlen($dcNew) != strlen($dcCode) || $dcNew != $dcCode ) {
					$object['DC']->content = $dcNew;
					$userMsg .= "<br> {$pid} : DC datastream created! <br>";
				} else {
					$userMsg .= "<br> {$pid} : DC datastream already up to date, so nothing done! <br>";
				}
			}
		}

	}

?>
