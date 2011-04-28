<?php

require_once ('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/tracker/ArtifactField.class.php');
require_once('common/tracker/ArtifactReportFactory.class.php');

$actual_tracker_id = $request->get('tracker_id');

$arf = new ArtifactReportFactory();
$reports = $arf->getReports($actual_tracker_id, 100);  // 100 is the user_id

echo '<select name="report_id">';
foreach ($reports as $report) {
    $selected = '';
    echo '<option value="'. $report->getID() .'" >'. $report->getName() .'</option>';
}
echo '</select>';

?>
