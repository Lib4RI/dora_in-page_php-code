<?php
// vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// SECURITY: This page is intended to be PUBLISHED but only for a few users!
$userOkAry = ['Stephanie','Bobby','Frank'];		// to get acccess for non-administrators
global $user;
if ( empty($user) || ( !in_array($user->name,$userOkAry) && !in_array('administrator',$user->roles) ) ) {
	drupal_set_title('Newspaper Recycling Dates');
	echo '<br>Please <a href="/user/login">log in</a> to access this page!<br><br>';
	return; // !
}
if ( @strpos($_SERVER['HTTP_HOST'],'dev') ) {
	echo "<br><b>WARNING:</b> This page does not proberly work on DEV (missing directories, no data transfer)!<br><p></p>";
}
drupal_set_title( ltrim(strrchr(':'.drupal_get_title(),':'),': ') ); // to cut off TOOL
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


// DEFAULTS:
$dateStamp = @strip_tags($_GET['date']);
if ( !intval($dateStamp) ) { $dateStamp = 10; /* as wished from Stephanie */ }

$lookBack = trim(strip_tags($_GET['back'])); // default: '-1 month'
$lookBack = abs($lookBack) ? ( abs($lookBack) < 30 ? '-'.abs($lookBack).' days' : '-'.ceil(abs($lookBack)/30).' months' ) : '-1 month';

$mailLimit = ( intval($_GET['limit']) > 0 ) ? intval($_GET['limit']) : 100;		// On maximum 100 e-mail address per mail link/batch

$mailTitle = trim(strip_tags($_GET['title'])); // default: '-1 month'
$mailTitle = empty($mailTitle) ? 'Lib4RI%20welcomes%20you%20at%20' : rawurlencode($mailTitle.' ');


$inst = strtolower(trim(strip_tags($_GET['inst'])));
$instAry = array('eawag','empa','psi','wsl');
if ( !empty($inst) && in_array($inst,$instAry) ) { $instAry = array($inst); }


$workAry = array(	/* look-up table for institute-specific SAP data/CSV structure */
	'eawag' => array(
		'mailIndex' => 'EMAIL',
		'transform' => 'strval',
	),
	'empa' => array(
		'mailIndex' => 'EMAIL',
		'transform' => 'strval',
	),
	'psi' => array(
		'mailIndex' => 'E-Mail',
		'transform' => 'strval',
	),
	'wsl' => array(
		'mailIndex' => 'EMAIL',
		'transform' => 'utf8_decode',
	),
);	

// $timeShift = -4 * 3600;	// to optimize CSV selection (timezones my bother)

// HERE WE GO...:
foreach( $instAry as $inst ) {

	$instName = ( strlen($inst) < 4 ? strtoupper($inst) : ucfirst($inst) );
	$timeBack = time() - strtotime($lookBack) - ( 24 * 3600 ); // we need to avoid to up with 12.Oct.22 - 12.Nov.22
	$mailIndex = $workAry[$inst]['mailIndex'];

	$dateAry = explode('-',$dateStamp);		// won't work: 1987.12.30.-2012.04.09.
	// try input: full date till full date e.g. 1987/12/30-2012/04/09
	$dateAry = array_map(function($t){ return strtr($t,'/.','--'); },$dateAry);
	$dateAry = array_map(function($t){ return rtrim($t,'-'); },$dateAry);
	$dateAry = @array_map('strtotime',$dateAry);
	if ( @intval($dateAry['0']) < 86400 ) {
		// try: 10-31
		$ary = array_combine( ['y','m','d'], array_map('intval',explode('-',date("Y-m-d"))) );
		$dateAry = explode('-',$dateStamp);
		if ( !empty($dateAry[0]) && $dateAry[0] < 32 ) {
			if ( $dateAry[0] > $ary['d'] ) {
				if ( $ary['m'] > 1 ) {
					$ary['m'] = $ary['m'] - 1;
				} else {
					$ary['m'] = 12;
					$ary['y'] = $ary['y'] - 1;
				}
				$timeBack -= ( 24 * 3600 ); // we need to avoid to up with 12.Oct.22 - 12.Nov.22
			}
			$ary['d'] = $dateAry[0];
		}
		$dateAry = array(
			'till' => strtotime( implode('-',$ary) ),
		);
		$dateAry['from'] = $dateAry['till'] - $timeBack;
	}
	elseif ( @intval($dateAry['1']) < 86400 ) {
		$dateAry = array(
			'till'  => $dateAry['0'],
			'from' => $dateAry['0'] - $timeBack,
		);
	} else { // for example 1987/12/30-2012/04/09 goes here:
		$dateAry = array(
			'till'  => max( $dateAry['0'], $dateAry['1'] ),
			'from' => min( $dateAry['0'], $dateAry['1'] ),
		);
	}

	// GET THE RIGHT CSVs:
	include_once('/var/www/html/sites/all/modules/lib4ri_author_update/includes/defaults.inc');

	if ( !( $dirData = lib4ri_author_update_get_site_default( $inst, 'data-local' ) ) ) {
		echo "<br>ERROR: Data directory '" . $dirData . "' not found!<br><br>";
		continue;
	}
	$dirData = '/var/www/html' . ltrim($dirData,'.') . '/';

	$csvAry = [];
	$scanAry = scandir( $dirData );
	foreach( $scanAry as $item ) {
		if ( substr($item,-4) != '.csv' ) { continue; }
		$ary = explode('_',strtok($item,'.'),4);
		$idx = strtotime(strtr($ary[3],'_','.')) . '__' . strtok($item,'.');
		$csvAry[$idx] = $item;
	}
	ksort($csvAry);
	$csvAry = array_reverse($csvAry);

	$fileAry = [];
	foreach( $csvAry as $idx => $item ) {
		
		if ( intval($idx) > ( 4 * 3600 + $dateAry['till'] ) ) {
			continue;
		}
		if ( @empty(!$_GET['dev']) ) {
			echo 'from: ' . date("Y-m-d H:i:s", $dateAry['till'] ) . ' till ' . date("Y-m-d H:i:s", $dateAry['from'] ) . '<br>';
			echo '&nbsp; &nbsp; ' . $item . ' @ ' . date("Y-m-d H:i:s", $idx ) . '<br>';
		}
		if ( @empty($fileAry['till']) ) {
			$fileAry['till'] = $item;
		}
		if ( intval($idx) < $dateAry['from'] ) {
			break;
		}
		$fileAry['from'] = $item;
	}


	/* DEV */ if ( @empty(!$_GET['dev']) ) { echo "<pre>" . print_r( $fileAry, 1 ) . "</pre><br>"; }


	// GET+COMPARE E-MAIL ADDRESSES:
	include_once('/var/www/html/sites/all/modules/lib4ri_author_update/includes/utilities.inc');

	$mailAry = [];
	$mailAry['from'] = lib4ri_author_update_csv_to_array( $dirData.$fileAry['from'], ';', $mailIndex, false, true );
	$mailAry['till'] = lib4ri_author_update_csv_to_array( $dirData.$fileAry['till'], ';', $mailIndex, false, true );

	$addrAry = [];
	foreach( $mailAry['till'] as $eMail => $userData ) {
		if ( @isset($mailAry['from'][$eMail]) ) { continue; }
		/* DEV */ if ( @empty(!$_GET['dev']) ) { echo "<br><pre>" . print_r( $userData, 1 ) . "</pre><br>"; }
		$userData = array_map($workAry[$inst]['transform'],$userData);
	//	$addrAry[] = rawurlencode($userData['FIRSTNAME'] . ' ' . $userData['LASTNAME'] . ' ') . '<' . $userData[$mailIndex] . '>';
		$addrAry[] = $userData[$mailIndex];
	}
	$addrTotal = sizeof($addrAry);

	$numBatches = ceil( $addrTotal / max($mailLimit,1) );
	$numPerBatch = intval(ceil( $addrTotal / $numBatches ));


	// OUTPUT:
	echo '<br>' . ( $addrTotal < 1 ? 'No' : $addrTotal ) . ' new E-Mail-Address' . ( $addrTotal == 1 ? '' : 'es' ) . ' for <b>' . $instName . '</b>';
	// There is 1 day of delay in the data as/when we got them, resp. everything we got, is from the day before, hence correct this in the display.
	echo ' from ' . date("d-M-Y", strtotime( implode('-',array_slice(explode('_',strtr($fileAry['from'],'.','_')),3,3)) ) - ( 24 * 3600 ) );
	echo ' until ' . date("d-M-Y", strtotime( implode('-',array_slice(explode('_',strtr($fileAry['till'],'.','_')),3,3)) ) - ( 24 * 3600 ) );
	echo ':<br><ul style="margin:0 0 0 0;">';
	

	if ( $addrTotal < 1 ) {
		echo '<i>No e-mail addresses found!</i><br></ul><br>';
		continue;  // next inst...
	}
	for($b=0;$b<intval($numBatches);$b++) {
		// echo "<br><pre>" . print_r( array_slice($addrAry,$b*$numPerBatch,$numPerBatch), 1 ) . "</pre><br>";
		$ary = array_slice($addrAry,$b*$numPerBatch,$numPerBatch);
		$idx = ($b+1) . '/' . $numBatches;
		echo ( $numBatches == 1 ) ? '<br>' : ('Batch ' . ($b+1) . '/' . $numBatches . ':<br>');
		echo '<a href="mailto:?bcc=' . implode(';',$ary) . '&subject=' . $mailTitle . $instName . '">';
	//	echo htmlentities(implode('; ',array_map('rtrim',array_map('urldecode',array_map('strip_tags',$ary))))) . '</a><br>';
		echo implode('; ',$ary) . '</a><br>';
	}
	echo '</ul><br>';
}
echo '<p></p>';



// HELP:
echo '<div style="font-size:10pt; color:#999;"><b>Examples:</b><br><ul style="margin:0 0 0 0;">';

$bsp = 'https://www.dora.lib4ri.ch/mail-delta?date=2022.09.25-2023.01.05';
echo '<li>' . 'Will show the new e-mail addresses between two dates' . ':<br>' . '<a style="color:#888;" href="' . $bsp . '">' . $bsp . '</a></li>';

$bsp = 'https://www.dora.lib4ri.ch/mail-delta?date=15';
echo '<li>' . 'Will take the 15th day of the month and look 1 month back' . ':<br>' . '<a style="color:#888;" href="' . $bsp . '">' . $bsp . '</a></li>';

$bsp = 'https://www.dora.lib4ri.ch/mail-delta?limit=10';
echo '<li>' . 'Will only create e-mail links/batches with 10 e-mail addresses on maximum' . ':<br>' . '<a style="color:#888;" href="' . $bsp . '">' . $bsp . '</a></li>';

echo '</ul><br><p></p>';
?>
