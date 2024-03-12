<?php

$desc = "This page is for WSL only, providing author PIDs and their SAP-IDs.";


$alias = 'aBcDefghijklm01234'; // Drupal alias to assign, keep this long+random for security concerns
$pin = ''; // optional PIN paramter (like '56789nopqrstuvWxYz') to access this page (not for admins though), empty for no restraint 


// Download test/example:
$exp1 = 'curl -A "Mozilla/5.0 (WSL-Lib)" "https://www.dora.lib4ri.ch/wsl/' . $alias . '/?' . ( empty($pin) ? '' : 'pin='.$pin.'&' ) . 'format=csv" -o "authors.csv"';
$exp2 = 'wget -U "Mozilla/5.0 (WSL-IT)" "https://www.dora.lib4ri.ch/wsl/' . $alias . '/?' . ( empty($pin) ? '' : 'pin='.$pin.'&' ) . 'format=json" -O "authors.json"';


$titleAry = array(
	'fake' => 'Official Author Initials',
	'real' => 'Author List SAP-ID + DORA-PID',
);

///////////////////////////////////////////////////////////////////////////////
// Parameter to run this page:

$wsl_aut_query = 'http://lib-dora-prod1.emp-eaw.ch:8080/solr/collection1/select?q=PID:wsl-authors%5c%3a*+AND+MADS_u1_mt:*&sort=PID+asc&rows=987654321&sort=PID+asc&rows=987654321&indent=true&wt=csv&csv.separator=%2c&fl=PID%2c+MADS_u1_mt';
if ( $pos = strpos($_SERVER['HTTP_HOST'],'-dev1.') ) { $wsl_aut_query = str_replace('lib-dora-prod1.emp-eaw.ch',$_SERVER['HTTP_HOST'],$wsl_aut_query); }

$ip_cidr_ary = array(
	'wsl' => array(	/* see e-mail from 2023-09-19, 14:20 */
		'wsl1' => '193.134.202.240/32',
		'wsl2' => '193.134.202.20/32',
	),
	'dev' => array(
		'fh1' => '10.193.69.198/32',
		'fh2' => '10.193.64.11/32',
	/*	'fc' => '10.193.87.10/32',	*/
	),
);

$transAry = array( /* to rename some Solr fields in the final output (optional) */
	'MADS_u1_mt' => 'SAP_ID', /* as in https://www.dora.lib4ri.ch/wsl/pers-data */
);

$ownUrl = $_SERVER['HTTP_X_FORWARDED_HOST'] . $_SERVER['REQUEST_URI']; // to replace/update the example links


///////////////////////////////////////////////////////////////////////////////
// IP check function (based on lib4ridora's lib4ridora_check_ip() function):
if ( !function_exists('lib4ridora_check_ip_within') ) {
	function lib4ridora_check_ip_within($ip_address = NULL, $cidr_list = '') {
	  if ( @isset($_GET['far-away']) ) {
		return FALSE;		// consider the intranet IP check as failed, as an (test) option for employees and associated workers to see how it looks like beyond the intranet.
	  }
	  if ( !( $ip_address = @trim($ip_address) ) ) {
		$ip_address = ip_address();
	  }
	  if ( !( $cidr_list = @trim($cidr_list,",;| \r\n\t\v\x00") ) ) {
		return FALSE;		// consider the intranet IP check as failed
      }
	  $cidr_ranges = explode(',',strtr(preg_replace('/\s+/','',$cidr_list),';|',',,'));
	  if ( empty($cidr_ranges) || !intval($ip_address) ) {
		return FALSE;		// consider the intranet IP check as failed
      }

	  // Transform an IPv4 CIDR block to an array containing the first and last IPs in the block.
	  $cidr_range_to_points = function ($cidr_string) {
		list($base, $bits) = explode('/', $cidr_string);
		$mask = pow(2, 32 - $bits) - 1;
		$start = ip2long($base) & ~$mask;
		$end = $start | $mask;
		return array($start, $end);
	  };
	  $client_ip = ip2long($ip_address);
	  $ip_result = FALSE;
	  foreach (array_map($cidr_range_to_points, $cidr_ranges) as $range) {
		list($start, $end) = $range;
		if ($start <= $client_ip && $end >= $client_ip) {
		  $ip_result = TRUE;
		  break;
		}
	  }
	  return $ip_result;
	}
}


///////////////////////////////////////////////////////////////////////////////
// Access control (with WSL IP or for DORA admins):
global $user;
$userName = 'Anonym'; // tmp;
if ( in_array('administrator',array_values($user->roles)) ) {
	$userName = 'Admin';
} elseif ( @isset($ip_cidr_ary['dev']) && lib4ridora_check_ip_within( ip_address(), @implode(',',$ip_cidr_ary['dev']) ) ) {
	$userName = 'Dev';
} elseif ( @isset($ip_cidr_ary['wsl']) && lib4ridora_check_ip_within( ip_address(), @implode(',',$ip_cidr_ary['wsl']) ) ) {
	$userName = 'WSL';
	if ( !empty($pin) && @urldecode($_GET['pin']) != $pin ) { // currently also for Devs
		drupal_set_title( $titleAry['fake'] );
		echo '<br>An additional authentication is need to use this page!<br><br>';
		return; // !
	}
} else { // for Anonymous
	drupal_set_title( $titleAry['fake'] );
	echo '<br>We are sorry, this page is not completely set up for you yet!<br><br>';
	return; // !
}
drupal_set_title( $titleAry['real'] );


///////////////////////////////////////////////////////////////////////////////
// HTML output - info and format selection:
$format = @strtolower($_GET['format']);
if( !in_array($format,['json','csv']) ) {
	echo '<br><b>Hello ' . $userName . '!</b><br>' . $desc . '<br><br>Please select a data format for the file to download manually: ';
	echo '<a href="./' . drupal_get_path_alias() . '/?format=json" target="_blank">JSON</a> | ';
	echo '<a href="./' . drupal_get_path_alias() . '/?format=csv" target="_blank">CSV</a>';
	if ( in_array($userName,['Admin','Dev','WSL']) ) {
		echo '<br><br>Command line examples:<br><tt style="line-height:18pt; font-size:12pt; letter-spacing:-1px;">';
		echo str_replace('www.dora.lib4ri.ch/wsl/'.$alias,$ownUrl,$exp1) . '<br>';
		echo str_replace('www.dora.lib4ri.ch/wsl/'.$alias,$ownUrl,$exp2) . '</tt>';
	}
	echo '<br><br>';
	return; // So the format must the set in all cases, WSL additionally will need the PIN
}


///////////////////////////////////////////////////////////////////////////////
// Solr file retrival and data optimization:
// Get data from Solr:
if ( !( $dataFile = @file_get_contents( ( $format == 'json' ) ? str_replace('wt=csv','wt=json',$wsl_aut_query) : $wsl_aut_query ) ) ) {
	echo '<br>Sorry, the requested data could not be retrieved!<br><br>';
	return;
}
if ( $format == 'json' ) { // with JSON 'extract' the result part:
	$dataAry = json_decode( $dataFile, true );
	$dataAry = $dataAry['response']['docs'];
	foreach( $dataAry as $aIdx => $aAry ) {	// SAP-ID is hold in an Array, make a String(list) out of it:
		$dataAry[$aIdx]['MADS_u1_mt'] = implode(',',$dataAry[$aIdx]['MADS_u1_mt']);
	}
	$dataFile = json_encode( $dataAry, JSON_PRETTY_PRINT );
}

// Solr field replacement (trivial, but sufficient(?)as unique Solr field names are):
$dataFile = str_replace( array_keys($transAry), array_values($transAry),$dataFile );


///////////////////////////////////////////////////////////////////////////////
// File download - create header + pass data file:
// for UTF8 header bytes: urldecode('%EF%BB%BF')
drupal_add_http_header('Content-Length', strlen($dataFile) );
drupal_add_http_header('Content-Type', ( $format == 'json' ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8' ) );
drupal_add_http_header('Content-Disposition', 'attachment; filename="' . 'WSL-Authors-with-SAP-ID.'.date("Y-m-d-H").'h.'.$format . '"');
print( $dataFile );
exit;

?>