<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$ 
require_once('pre.php');
require_once('graph_lib.php');
require('./project_stats_utils.php');


//
//  This is some of the ugliest code I've ever written. <sob>
//

if ( !$group_id ) {
	$group_id = 0;
}

if ( !span ) {
	$span = 30;
}
if ( $span < 4 ) { 
	$span = 4; 
}


$today = localtime( time(), 1 );

//
// How does Uriah say it? PHEAR the date manipulation.
//
if ( $view == "monthly" ) {
	$begin_time = mktime( 0, 0, 1, $today['tm_mon'] - $span + 2, 1, $year );
} elseif ( $view == "weekly" ) {
	$foo = mktime( 0, 0, 1, $today['tm_mon'] + 1, $today['tm_mday'] - (7 * $span), $year );
	list( $begin_time, $bar ) = week_to_dates( strftime("%U", $foo ) + 1, strftime("%Y", $foo ) );
} else {
	$begin_time = mktime( 0, 0, 1, $today['tm_mon'] + 1, $today['tm_mday'] - $span, $year );
}
$year = date("Y", $begin_time);
$month = sprintf("%02d", date("m", $begin_time) );
$day = date("d", $begin_time);


$sql  = "SELECT month,day,downloads,(site_views + subdomain_views) as views ";
$sql .= "FROM stats_project ";
$sql .= "WHERE ( (( month = " . $year . $month . " AND day >= " . $day . " ) OR ";
$sql .= "( month > " . $year . $month . " )) AND group_id = " . $group_id . " ) ";
$sql .= "GROUP BY month,day ORDER BY month,day";

$res = db_query( $sql );
$i = 0;
while ( $row = db_fetch_array($res) ) {
        $xdata[$i]          = $i;
        $xlabel[$i]         = (substr($row['month'],4) + 1 - 1) . "-" . sprintf("%02d",$row['day']);
        $ydata1[$i]         = $row["views"];
        $ydata2[$i]         = $row["downloads"];
        $i++;
}

if ($i==1) {
    /*    // In case there's nothing to graph, show query trace
	  echo "No data to graph";
	  site_project_footer(array());
	  exit;*/
    
    // In case there is no data set or just 1 record then
    // simulate 2 records. otherwise it Graph_lib crashes
    $xdata[$i]=$i;
    $xlabel[$i]         = $month . "-" . $day;
    $ydata1[$i]         = 0;
    $ydata2[$i]         = 0;
    $i=1;
    $xdata[$i]=$i;
    $xlabel[$i]         = $month . "-" . $day+1;
    $ydata1[$i]         = 0;
    $ydata2[$i]         = 0;
    $i=2;
}

$graph = new Graph(600, 350);

$graph->addDebug( "We appended $i rows of data to the graphing set." );
$graph->addDebug( "$begin_time" );
$graph->addDebug( "$sql" );

$data1 = $graph->AddData($xdata,$ydata1,$xlabel);
$data2 = $graph->AddData($xdata,$ydata2,$xlabel);

$graph->DrawGrid('gray');
$graph->LineGraph( $data1, 'red' );
$graph->LineGraph( $data2, 'blue' );
$graph->SetTitle( "CodeX Statistics: " . group_getname($group_id) );
$graph->SetSubTitle("Page Views (red) and Downloads (blue) for the past $i days");
$graph->SetxTitle('Date');
$graph->SetyTitle('Views (red) / Downloads (blue)');
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
