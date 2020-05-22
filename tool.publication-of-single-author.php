<?php
$pid = @rawurldecode($_GET['pid']);		// = eawag-authors:1234

$mTime = time();
$instAry = array('Eawag','Empa','PSI','WSL');
$pidAux = strtolower($instAry[($mTime % 4)]) . '-authors:' . substr(strval($mTime),-4);

if ( @empty($pid) ) { echo "No Author PID specified. <br>Please attach onto the URL for example <span style='color:green'>?pid={$pidAux}</span><br><br>"; return; }
if ( !strpos($pid,':') ) { echo "ERROR: Got a strange PID '{$pid}'... !?<br><br>"; return; }
if ( !( $object = islandora_object_load($pid) ) ) { echo "ERROR: Problem loading object '{$pid}'... !?<br><br>"; return; }
if ( !( $dcContent = $object['DC']->Content ) ) { echo "ERROR: Problem loading properites for object '{$pid}'... !?<br><br>"; return; }

$inst = strtok(strtr($pid,'-',':'),':');

$autTitle = strtok(substr(stristr($dcContent,'<dc:title>'),10).'<','<');
$link = "https://www.dora.lib4ri.ch/{$inst}/islandora/object/{$pid}/manage/datastreams/";
$link = "<a target='_blank' href='{$link}'>{$autTitle}</a>";
echo 'Publications in DORA for <b>' . $link . '</b> (' . $pid . ")<br>\r\n";


// http://lib-dora-prod1.emp-eaw.ch:8080/solr/collection1/select?q=PID:wsl%5c%3a*+AND+mods_name_personal_nameIdentifier_authorId_mt:wsl-authors%5c%3a1176&fl=PID&sort=PID+asc&rows=987654321&wt=csv&indent=true&csv.separator=%7c
// http://lib-dora-prod1.emp-eaw.ch:8080/solr/collection1/select?q=PID:psi%5c%3a*+AND+mods_name_personal_alternativeName_nameIdentifier_authorId_mt:psi-authors%5c%3a3162&fl=PID&sort=PID+asc&rows=987654321&wt=csv&indent=true&csv.separator=%7c
$solrField = ( $inst == 'psi' ? 'mods_name_personal_alternativeName_nameIdentifier_authorId_mt' : 'mods_name_personal_nameIdentifier_authorId_mt' );
$pubUrl = "http://lib-dora-" . ( @strpos($_SERVER['HTTP_HOST'],'prod1') ? 'prod1' : 'dev1' ) . ".emp-eaw.ch:8080/solr/collection1/select";
$pubUrl .= "?q=PID:[inst]%5c%3a*+AND+" . $solrField . ":[pid]&fl=PID&sort=PID+asc&rows=987654321&wt=csv&indent=true&csv.separator=%7c";


	$url = str_replace('[inst]',$inst,$pubUrl);
	$url = str_replace('[pid]',str_replace(':','%5c%3a',$pid),$url);
	if ( $bibAry = @file($url) ) {
		array_shift($bibAry);
		if ( empty($bibAry) ) { echo "<br><i>No publications found for this author in DORA.<br><br>"; return; }
		foreach( $bibAry as $pidPub ) {
			$link = "https://www.dora" . ( @strpos($_SERVER['HTTP_HOST'],'prod1') ? '' : '-dev1' ) . ".lib4ri.ch/";
			$link .= $inst . "/islandora/object/{$pidPub}/manage/datastreams/";
			$link = "- <a target='_blank' href='{$link}'>{$pidPub}</a>";
			echo $link . "<br>\n\r";
		}
	}
	else { echo "ERROR: Problem retrieving publication list... !?<br><br>"; return; }


echo '<br>';
?>

