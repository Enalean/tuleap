<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: users_graph.png 1405 2005-03-21 14:41:41Z guerin $
require_once('pre.php');
require_once('graph_lib.php');

   // require you to be a member of the super-admin group
session_require(array('group'=>'1','admin_flags'=>'A'));

$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId');
if ( ! $group_id ) {
	$group_id = 0;
}

$year = $request->getValidated('year', 'uint');
if ( ! $year ) {
	$year = gmstrftime("%Y", time() );
}

$sql = "SELECT month,day,new_users,new_projects FROM stats_site ORDER BY month ASC, day ASC";
$res = db_query( $sql );

$i = 0;
while ( $row = db_fetch_array($res) ) {
        $xdata[$i]          = $i;
	$xlabel[$i]         = (substr($row['month'],4) + 1 - 1) . "/" . $row['day'];
        $ydata1[$i]         = $row["new_users"];
        $ydata2[$i]         = $row["new_projects"];
        ++$i;
}

$graph = new Graph( 750, 550 );

$data1 = $graph->AddData( $xdata, $ydata1, $xlabel );
$data2 = $graph->AddData( $xdata, $ydata2, $xlabel );

$graph->DrawGrid('gray');
$graph->LineGraph($data1,'red');
$graph->LineGraph($data2,'blue');
$graph->SetTitle( "New Additions, by Day" );
$graph->SetSubTitle('New Users (RED), New Projects (BLUE)');
$graph->SetxTitle('Date');
$graph->SetyTitle('Users (RED) / Projects (BLUE)');
$graph->DrawAxis();
//$graph->showDebug();

$graph->ShowGraph('png');

?>
