<?php

$server_job = ( strchr($_SERVER['HTTP_HOST'],"-prod1.") ? "prod" : "dev" );
$name_space = ( strchr("eawag,empa,psi,wsl",strtok(substr($_SERVER['REQUEST_URI'],1)."/","/")) ? strtok(substr($_SERVER['REQUEST_URI'],1)."/","/") : "*" );
$host_label = ( $name_space == "*" ) ? "Lib4RI site" : ( ( ( strlen($name_space) < 4 ) ? strtoupper($name_space) : ucfirst($name_space) ) . " sub-site" );

$size_exp_ft = 10000;	// full-text minimum size to be rechecked, in bytes
$size_min_ft = 200;
$size_min_gz = 500;		// full-text minimum size when zipped, in bytes
$check_multi_gz = 3;
$add_ds_ft = ( @isset($_GET['addft']) || @isset($_GET['addFT']) );		// wether or not to add a full-text DS if there isn't any yet.
$add_ds_max = max( @intval($_GET['addft']), @intval($_GET['addFT']) ) - 1;
$wget_pause = @intval($_GET['wget']);			// for our bash/bat list, to pause between Wget commands (in seconds).

global $user;
$user_agent = "Mozilla/5.0 (Lib4RI-FT-Fix)";


$link_host = "https://www.dora" . ( $server_job == "prod" ? "" : "-dev" ) . ".lib4ri.ch";

echo "<b>This page will list publications which have a very small full-text content.</b><br>\r\n";
echo "<br><b>Warning:</b><br>\r\n - This check may " . ( $server_job == "prod" ? " seriously slow down the PROD" : " slow down the DEV" ) . " server!</b><br>\r\n";
echo "<br><b>Logic/Restrictions:</b><br>\r\n";
echo "- The existing full-text must be smaller that {$size_exp_ft} bytes, otherwise it will be assumed the full-text is fine (so no custom OCR required).<br>\r\n";
echo "- The full-text content will be compressed to reduce redundancies, and if greater than " . ($size_min_gz*$check_multi_gz) . " we assume OCR was sucessfully applied.<br>\r\n";
echo "- If the compressed full-text size per PDF page is smaller than ~{$size_min_gz} bytes we assume the OCR is bad.<br>\r\n";


$instAry = array();

if ( !user_is_logged_in() && $_SERVER['HTTP_USER_AGENT'] != $user_agent ) {
	echo "<br><i><b>You must be logged in to enjoy the full potential of this page!</b></i><br><br>\r\n";
} else {
	if ( @!isset($_GET['inst']) ) {
		echo "<br><b>Parameters/Options:</b><br>\r\n";
		echo "- Add for example '?inst={$name_space}' onto the URL to start this check for the Empa publications. &nbsp; <i>&lt;== required</i><br>\r\n";
		echo "- Add '&addFT' onto the URL  to add a full-text datastream where missing. Note: a time-out may happen, use then the 'wget' work-around.<br>\r\n";
		echo "- Add '&wget' to get a list of Wget command for a bash/bat list to produce a missing full-text datastream just for a single publication,<br>\r\n";
		echo "&nbsp; This will help to avoid time-outs, however this page here must be (temporarily) set to 'published'.<br>\r\n";
		echo "<br><b>Example:</b><br>\r\n";
		$url = $link_host . strtok($_SERVER["REQUEST_URI"]."?","?") . "?inst=" . $name_space;
		echo "- <a href='{$url}'><u>{$url}</u></a> would check/list publications with missing/partial full-text datastreams.<br>\r\n";
	} else {
		if ( empty($_GET['inst']) ) { $instAry = array( 'all' => "" ); }
		else { $instAry = explode(",",$_GET['inst']); }
		ignore_user_abort(true);
		drupal_set_time_limit(360);			// effective at all?
	}

	// OVERRIDE 1/3:
	$ftSize_got = @intval($_GET['ft']);		// size of the FT according to Solr
	$pid_got = @rawurldecode($_GET['pid']);		// $pid to treat
	if ( strchr($pid_got,":") && @isset($_GET['ft']) ) {
		$instAry = array( strtok($pid_got.":",":") );
	}
	if ( @isset($_GET['wget']) ) { $add_ds_ft = false; }
	else { echo "<br>\r\n"; }	
}

foreach( $instAry as $inst ) {

	if ( strtolower($inst) == "lib4ri" || strtolower($inst) == "main" ) { $inst = ""; }
	
	$sizeAry = array();
	$link_base = $link_host . ( empty($inst) ? "" : "/".$inst );

	// OVERRIDE 2/3:
	if ( strchr($pid_got,":") && @isset($_GET['ft']) ) {
		$sizeAry[] = array( $pid_got, $ftSize_got );
	}
	else {
		// Note: $_SERVER["HTTP_HOST"] is e.g. 'lib-dora-prod1.emp-eaw.ch' and not the host address as shown (it's the rev.proxy)!
		$solr_url = "http://lib-dora-" . ( strchr($_SERVER["HTTP_HOST"],"prod") ? "prod1" : "dev1" ) . ".emp-eaw.ch:8080/solr/collection1/select?";
				$solr_url .= "q=PID%3A" . ( empty($inst) ? "*" : $inst ) . "%5C%3A*+AND+(fedora_datastream_info_PDF_ID_mt%3A*+OR+fedora_datastream_info_PDF1_ID_mt%3A*+OR+fedora_datastream_info_PDF2_ID_mt%3A*+OR+fedora_datastream_info_PDF3_ID_mt%3A*)&sort=PID+asc&rows=987654321&fl=PID%2C+fedora_datastream_latest_FULL_TEXT_SIZE_ms&wt=csv&indent=true";
		if ( @isset($_GET['dev']) ) { echo "Solr Request:<br>" . $solr_url . "</br><br>\r\n"; }
		if ( @isset($_GET['dry']) ) { continue; }
		$csvAry = file($solr_url);

		array_shift($csvAry);
		foreach( $csvAry as $row ) {
		//	echo $row;
			$rowAry = str_getcsv($row);
			$pidAry = explode(":",$rowAry[0]);
			// catch+skip e.g. 'wsl:forum', 'eawag:publications' or 'empa-authors:1234' (specially on for the main-site):
			if ( intval($pidAry[1]) && in_array( $pidAry[0], array('eawag','empa','psi','wsl') ) ) {
				$pid = $rowAry[0];
				$ftSize = intval($rowAry[1]);
//				$idx = str_pad( strval($ftSize), 9, "0", STR_PAD_LEFT ) . $pid;
// test
				$pidAry = explode(":",$pid);
				$idx = $pidAry[0] . "_" . str_pad( $pidAry[1], 9, "0", STR_PAD_LEFT );

				$sizeAry[$idx] = array( $pid, $ftSize );
			}
		}
		ksort($sizeAry);
		$sizeAry = array_reverse($sizeAry, true);
	}

	$user = user_load( 1 );
	drupal_static_reset('islandora_get_tuque_connection');	// You need this if you want to connect to Fedora with the new user.
	// trying to make the currently connected $user(s) the owner of $object, so we can modify it.
	// Note, islandora_object_load($pid) won't be sufficient since this may let you get a cached one incl. its (former) owner.
	$tuque = islandora_get_tuque_connection($user);

	if ( @isset($_GET['wget']) ) { echo "<br><i>#!/bin/sh</i><br>\r\n"; }
	$pubCount = 0;
	$fixCount = 0;
	foreach( $sizeAry as $ary ) {
		$ftSize = $ary[1];
		if ( $ftSize >= $size_exp_ft ) { continue; }
		if ( $add_ds_max >= 0 && $fixCount > $add_ds_max ) { break; }
		$pubCount++;
		$pid = $ary[0];
		$link_tag = "<a href='{$link_base}/islandora/object/{$pid}/manage/datastreams' target='_blank'>{$pid}</a>";
		$object = NULL;
	/*
		$tuque = islandora_get_tuque_connection();
		$tuque->cache->delete($pid);
		$object = @islandora_object_load($pid);
	*/
		$object = new FedoraObject($pid, $tuque->repository);
//		if ( !( $object ) ) { continue; }
// test
		if ( strtok($pid,":") == "psi" && intval(substr(strchr($pid,":"),1)) > 4999 ) {
			$skip = false;
			if ( !( $object ) ) {
				echo ( $pubCount ) . ".) " . $link_tag . " could <b>NOT BE LOADED</b>!<br>\r\n";
				continue;
			}
			if ( @!isset($object['PDF']) ) {		// regular case for several publications
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>NO PDF</b> datastream!<br>\r\n";
				continue;
			}
			if ( @!isset($object['PREVIEW']) ) {		// regular case for several publications
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>no Preview</b> datastream!<br>\r\n";
				$skip = true;
			}
			if ( @!isset($object['FULL_TEXT']) ) {		// regular case for several publications
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>no Full-Text</b> datastream!<br>\r\n";
				$skip = true;
			}
			if ( @!isset($object['TN']) ) {		// regular case for several publications
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>no ThumbNail</b> datastream!<br>\r\n";
				$skip = true;
			}
			if ( @!isset($object['PDF_PDF-A']) ) {		// regular case for several publications
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>no PDF/A</b> datastream!<br>\r\n";
				$skip = true;
			}
			if ( $skip ) { continue; }
		}
		elseif ( !( $object ) ) { continue; }


		if ( @isset($_GET['wget']) ) {		// OVERRIDE 3/3:
			if ( @!isset($object['FULL_TEXT']) ) {
				$out = "/tmp/ft-ocr-new." . strtr($pid,":","-") . ".html";
				$cmd = "wget -U '{$user_agent}' -O {$out} \"" . $link_host;
				$cmd .= strtok($_SERVER["REQUEST_URI"]."?","?") . "?addft&pid={$pid}&ft={$ftSize}\"";
				echo "<i>" . $cmd . "</i><br>\r\n";
				if ( $wget_pause ) { echo "<i>sleep " . $wget_pause . "</i><br>\r\n"; }
				$fixCount++;
			}
			continue;
		}

		$is_ft_ds_set = FALSE;
		$is_ft_aux = FALSE;
		$ft = "";
		if ( @isset($object['FULL_TEXT']) ) {
			$ft = $object['FULL_TEXT']->content;
			$is_ft_ds_set = TRUE;
			$is_ft_aux = strchr(substr($ft,0,90),"Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy");
		} else {
			if ( !$add_ds_ft ) {
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>no full-text</b> datastream! Not enabled to create it, so skipping...<br>\r\n";
			} else {
				echo ( $pubCount ) . ".) " . $link_tag . " has <b>no full-text</b> datastream! Trying to recreate derivatives...<br>\r\n";
			}
		//	flush();

			$dirNoFT = "/tmp/check-no-full-text";
			if ( @!is_dir($dirNoFT) ) { mkdir($dirNoFT); }
			$html = $dirNoFT . "/Open-" . strtr($pid,":","-") . ".html";
			if ( @filesize($html) < 32 && ( $fp1 = @fopen( $html, "wb" ) ) ) {
				fwrite( $fp1, "<html><head><meta http-equiv='refresh' content='0;url={$link_base}/islandora/object/{$pid}/manage/datastreams'/></head><body></body></html>" );
				fclose( $fp1 );
			}
			if ( !$add_ds_ft ) { continue; }

			module_load_include('inc', 'islandora', 'includes/derivatives');
			islandora_do_derivatives($object, array( 'force' => FALSE, 'source_dsid' => 'PDF' ) );
			$fixCount++;
			if ( !strchr($pid_got,":") || @!isset($_GET['ft']) || @!isset($_GET['ft'] ) ) {
				$dsDoTime = ( ( intval( variable_get('lib4ri_pdfa_gs_delay', '4' ) ) + 1 ) >> 1 ) + 2;
				sleep($dsDoTime);
			}

		//	$tuque = islandora_get_tuque_connection($user);
			$tuque->cache->delete($pid);
			$object = islandora_object_load($pid);
			if ( @!isset($object['FULL_TEXT']) ) { 
				echo "<font color='#e80808'>" . ( $pubCount ) . ".) " . $link_tag . " Error, <b>still</b> no full-text, skipping...</font><br>\r\n";
		//		flush();
				continue;
			}
			$ft = $object['FULL_TEXT']->content;
			$ftSize = strlen($ft);		// to pretend of being indexed already.
			$is_ft_ds_set = TRUE;
			$is_ft_aux = strchr(substr($ft,0,90),"Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy");
			echo ( $pubCount ) . ".) " . $link_tag . " has <b>now</b> a full-text datastream of <b>" . $ftSize . "</b> bytes.<br>\r\n";
		}

		$pdfPath = "";
		$ftPath = "";
		$ftSizeFile = 0;
		$tStamp = strval( time() );
		$link_tag = "<a href='{$link_base}/islandora/object/{$pid}/manage/datastreams' target='_blank'>{$pid}</a>";
		$prcPid = -1;

		// First, if we have a suspiciously small full-text, then try to re-create it with a newer pdftotext or GS:
		if ( !$is_ft_ds_set || $is_ft_aux || abs( strlen($ft) - $ftSize ) > 5 ) {
			if ( $is_ft_aux ) {
				echo "<i>" . ( $pubCount ) . ".) " . $link_tag . " has an auxiliary full-text of " . strlen($ft) . " bytes</i><br>\r\n";
			} else {
				echo "<i>" . ( $pubCount ) . ".) " . $link_tag . " has a suspicious full-text (in DS " . strlen($ft) . " bytes, found " . $ftSize . " bytes)</i><br>\r\n";
			}
			if ( empty($pdfPath) || empty(filesize($pdfPath)) ) {
				$pdfPath = '/tmp/' . strtr($pid,":","-") . '.MainPdfDS.' . $tStamp . '.pdf';
				$object['PDF']->getContent($pdfPath);
			}
			if ( @filesize($pdfPath) < 500 ) {		// arbitrary assumption of PDF minimum size
				echo "<font color='#e80808'>" . ( $pubCount ) . ".) " . $link_tag . " <b>broken/no PDF content</b>, skipping...</font><br>\r\n";
				if ( @is_file($pdfPath) ) { drupal_unlink($pdfPath); }
				continue;
			}

			$ftPath = '/tmp/' . strtr($pid,":","-") . '.MainPdfDS.' . $tStamp . '.p2t.txt';
			$cmdFT = "/usr/bin/pdftotext {$pdfPath} {$ftPath}";		// older pdftotext version installed though...
			exec($cmdFT);
			$ftSizeFile = @filesize($ftPath);

			if ( $ftSizeFile <= $size_min_ft || $ftSizeFile <= $ftSize ) {
				$ftPath2 = '/tmp/' . strtr($pid,":","-") . '.MainPdfDS.' . $tStamp . '.GS.txt';
				$cmdFT = "/usr/bin/gs-926 -dBATCH -dNOPAUSE -sDEVICE=txtwrite -dFITLERIMAGE -sOutputFile={$ftPath2} {$pdfPath}";
			//	exec($cmdFT);
				$descAry = array( /* stdin: */ 0 => array('pipe', 'r'), /* stdout: */ 1 => array('pipe', 'w') );
				if ($prc = proc_open($cmdFT, $descAry, $retPipes) ) {
					$prcAry = proc_get_status($prc);
					$prcPid = $prcAry['pid'];
					proc_close($prc);
				}

				$ftSizeFile2 = @filesize($ftPath2);
				if ( ( $ftSizeFile2 * 8 ) > ( $ftSizeFile * 9 ) ) {
					drupal_unlink($ftPath);
					$ftPath = $ftPath2;
					$ftSizeFile = $ftSizeFile2;
				}
				if ( $ftSizeFile <= $size_min_ft || $ftSizeFile <= $ftSize ) {
					$ftSizeFile = 0;
				}
			}
		}
		if ( $ftSizeFile && !empty($ftPath) ) {
			if ( !$is_ft_ds_set ) {
				echo "<i>" . ( $pubCount ) . ".) " . $link_tag . " got a full-text (now " . strlen($ft) . " bytes) - creating+checking it...</i><br>\r\n";
				$ftDs = $object->constructDatastream('FULL_TEXT', 'M');
				$ftDs->label = "FULL_TEXT";
				$ftDs->mimetype = "text/plain";
				$ftDs->setContentFromFile($ftPath, FALSE);
				$object->ingestDatastream($ftDs);
			} else {
				echo "<i>" . ( $pubCount ) . ".) " . $link_tag . " got a new full-text (now " . strlen($ft) . " bytes) - rechecking it...</i><br>\r\n";
				$ftDs = $object['FULL_TEXT'];
				$ftDs->setContentFromFile($ftPath, FALSE);
		//		$object->ingestDatastream($ftDs);
			}
		}

		// Second, if the current/created full-text is (still) to small, then run the OCR on the PDF:
		$gzSize = strlen(gzcompress($ft));
		if ( $gzSize <= ( $size_min_gz * $check_multi_gz ) ) {
			$pageTotal = 0;
			if ( $gzSize > $size_min_gz ) {
				if ( empty($pdfPath) || empty(filesize($pdfPath)) ) {
					$pdfPath = '/tmp/' . strtr($pid,":","-") . '.MainPdfDS.' . $tStamp . '.pdf';
					$object['PDF']->getContent($pdfPath);
				}
				$cmdInfo = "/usr/bin/pdfinfo {$pdfPath}";
				$pageTotal = intval(substr(strchr(shell_exec($cmdInfo),"Pages:"),7));
				if ( $pageTotal > 1 ) {
					if ( ( floatval($gzSize) / pow($pageTotal,7/8) ) > $size_min_gz ) { /* continue */ $pageTotal = -1; }
				}
				elseif ( $pageTotal == 1 ) { /* continue */ $pageTotal = -1; }
			}

			if ( $pageTotal != -1 ) {
				echo $pubCount . ".) " . $link_tag . " / full-text of " . $ftSize . " bytes for ";
				if ( $pageTotal == 0 ) { echo "? pages"; }
				elseif ( $pageTotal == 1 ) { echo "1 page"; }
				else { echo $pageTotal . " pages"; }
				echo " / compressed: " . $gzSize . " bytes<br>\r\n\r\n";
			}
		}

		// clean up:
		if ( !empty($pdfPath) ) { drupal_unlink($pdfPath); }
		if ( !empty($ftPath) ) { drupal_unlink($ftPath); }
		if ( $prcPid != -1 && file_exists("/proc/{$prcPid}") ) {
			echo "<font color='#e80808'>" . ( $pubCount ) . ".) " . $link_tag . " Warning, <b>GS process hanged</b>, trying to kick it out...</font><br>\r\n";
			exec("kill -9 {$prcPid}");
		}
	}
	echo "<br>{$pubCount} publication" . ( $pubCount == 1 ? "" : "s" ) . " examined with no full-text datastream or one smaller than {$size_exp_ft} bytes.<br>\r\n";
	echo "Full-text datastream" . ( @isset($_GET['wget']) ? " <b>to be</b>" : "" ) . " fixed/recreated for {$fixCount} publication" . ( $fixCount == 1 ? "" : "s" ) . ".<br><br>\r\n";
}
?>
