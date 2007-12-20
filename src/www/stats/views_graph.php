<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: views_graph.png 3641 2006-09-11 09:12:04Z guerin $
require_once('pre.php');
require_once('graph_lib.php');

   // require you to be a member of the super-admin group
session_require(array('group'=>'1','admin_flags'=>'A'));

if ( ! $group_id ) {
	$group_id = 0;
}

if ( ! $year ) {
	$year = gmstrftime("%Y", time() );
}

$sql = "SELECT month,day,site_views,subdomain_views FROM stats_site ORDER BY month ASC, day ASC";
$res = db_query( $sql );

$i = 0;
while ( $row = db_fetch_array($res) ) {
        $xdata[$i]          = $i;
	$xlabel[$i]         = (substr($row['month'],4) + 1 - 1) . "/" . $row['day'];
        $ydata1[$i]         = $row["site_views"] + $row["subdomain_views"];
        ++$i;
}

$graph = new Graph( 750, 550 );

$data1 = $graph->AddData( $xdata, $ydata1, $xlabel );

$graph->DrawGrid('gray');
$graph->LineGraph($data1,'red');
$graph->SetTitle( "CodeX Page Views" );
$graph->SetSubTitle("Page Views (RED) since beginning of time ( $i days )");
$graph->SetxTitle('Date');
$graph->SetyTitle('Views (RED)');
$graph->DrawAxis();
//$graph->showDebug();

// If PHP3 then assume GD library < 1.6 with only GIF Support
// if PHP4 then we have GD library >= 1.6 with only PNG Support
if (substr(phpversion(),0,1) == "3") {
    $graph->ShowGraph('gif');
} else {
    $graph->ShowGraph('png');
}


?>
