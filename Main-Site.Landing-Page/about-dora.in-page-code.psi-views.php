This is a temporary help page.<br>
<?php

$queryYear = @intval($_GET['year']);
if ( $queryYear < 1900 ) { $queryYear = intval(date("Y")) - 1; }

echo "Although not published, this page is intended to deliver stats for the year <b>{$queryYear}</b> as currently used e.g. on the <a href='https://www.dora.lib4ri.ch/about' target='_blank'>About</a> page.<br><br>";
$link = url("/stats.vw", array('absolute' => true)) . "?year=2020";
echo "Use this link to change the year via URL: <a href='{$link}' target='_blank'>{$link}</a><br><hr>";

$userID = "0";	// 0: Anonymous, otherwise leave empty

$period = array(
	'start' => $queryYear . '-01-01 00:00:00',
	'end' => $queryYear . '-12-31 23:59:59',
);
$colAry = array(
	0 => "",
	3 => "psi:external",		/* 3 means the external collection, see: SELECT * FROM islandora_usage_stats_objects WHERE id=3; */
	5 => "psi:publications",		/* 5 means the psi:publications collection, see: SELECT * FROM islandora_usage_stats_objects WHERE id=5; */
);

$pid = NULL; // = "psi:1234";
$dsid = NULL; // = "PDF2";

global $base_path;
$inst = trim($base_path,'/');
$inst = ( strlen($inst) < 4 ) ? strtoupper($inst) : ucfirst($inst);

// module_load_include('inc', 'islandora_usage_stats', 'includes/utilities');

foreach( $colAry as $colid => $colName ) {
	
/*
	$query = db_select('islandora_usage_stats_object_ds_access_log', 'ds_log' );
	$query->join('islandora_usage_stats_datastreams', 'dses', 'dses.id = ds_log.ds_id');
	$query->join('islandora_usage_stats_objects', 'objs', 'objs.id = dses.pid_id');
	$query->join('islandora_usage_stats_collection_access_log', 'col_log', 'col_log.object_access_id = ds_log.id');
	$query->addExpression('COUNT(dses.dsid)');
	if ($pid) {
		$query->condition('objs.pid', $pid);
	}
	if ($dsid) {
		$query->condition('dses.dsid', $dsid);
	}
*/
	$query = db_select('islandora_usage_stats_object_access_log', 'logs' );
	$query->join('islandora_usage_stats_objects', 'objs', 'objs.id = logs.pid_id');
	$query->join('islandora_usage_stats_collection_access_log', 'col_log', 'col_log.object_access_id = objs.id');
	if ( $userID !== "" ) {
		$query->condition('uid', intval($userID));	/* user id, zero for anonymous */
	}
	$query->condition('time', array(strtotime($period['start']),strtotime($period['end'])), 'BETWEEN');
//	$query->condition('objs', $pid, '=');
	if ($colid) {
		$query->condition('col_log.collection', $colid);
	}

	$result = $query->countQuery()
    ->execute()
    ->fetchField();
  
	echo "<br><tt><pre>Full Query Results:<br>" . rtrim(print_r( $result, TRUE )) . "</pre></tt>";

	$sumViews = ( is_array($result) ? sizeof($result) : intval($result) );

	echo "<br>For {$inst} totally <b> {$sumViews} </b> publication views ";
	echo ( $colid ? "in collection '" . $colName . "'" : "in <b>all</b> collections" );
	echo "<br> between {$period['start']} and {$period['end']}.<br><br>";
}
echo "<br><br>\r\n";
?>
