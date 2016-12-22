<?php

require_once('pre.php');

require_once($GLOBALS['jpgraph_dir'].'/jpgraph.php');
require_once($GLOBALS['jpgraph_dir'].'/jpgraph_pie.php');

$pass_count = $request->getValidated('p', 'uint', 0);
$fail_count = $request->getValidated('f', 'uint', 0);
$skip_count = $request->getValidated('s', 'uint', 0);
$total_count = $pass_count + $fail_count + $skip_count;

// graph size
$graph = new PieGraph(250,150); 

// graph title
$graph->title-> Set($GLOBALS['Language']->getText('plugin_hudson','project_job_testresults'));

// graph legend
$pass_legend = $GLOBALS['Language']->getText('plugin_hudson','pass_legend', array($pass_count));
$fail_legend = $GLOBALS['Language']->getText('plugin_hudson','fail_legend', array($fail_count));
$skip_legend = $GLOBALS['Language']->getText('plugin_hudson','skip_legend', array($skip_count));

$array_legend = array($pass_legend, $fail_legend);
$array_value = array($pass_count, $fail_count);
$array_color = array('blue', 'red');
if ($skip_count != 0) {
    $array_legend[] = $skip_legend;
    $array_value[] = $skip_count;
    $array_color[] = 'black';
}

// Init pie chart with graph values
$pp  = new PiePlot($array_value);

// pie chart legend
$pp->SetLegends($array_legend);

// pie chart color values
// Pass is blue and Failed is red (Skip is black)
$pp->SetSliceColors($array_color); 

// pie chart position
// the pie chart is a little bit on the left (0.35) and at the bottom (0.60)
$pp->SetCenter(0.35, 0.60);

$graph->Add($pp);

// display graph
$graph->Stroke(); 

?>