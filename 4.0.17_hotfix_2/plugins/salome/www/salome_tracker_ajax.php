<?php

require_once ('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');

$pm = ProjectManager::instance();
$grp = $pm->getProject($request->get('group_id'));
$actual_tracker_id = $request->get('tracker_id');
$special_field = $request->get('special_field');

if (!$grp || !is_object($grp)) {
    return false;
} elseif ($grp->isError()) {
    return false;
}

$at = new ArtifactType($grp, $actual_tracker_id);
if (!$at || !is_object($at)) {
    return false;
} elseif (! $at->userCanView()) {
    return false;
} elseif ($at->isError()) {
    return false;
}

$afsf = new ArtifactFieldSetFactory($at);
$aff = new ArtifactFieldFactory($at);
$fields = $aff->getAllUsedFields();

echo '<select name="'.$special_field.'">';
echo ' <option value="0">--</option>';
foreach ($fields as $field_name => $field) {
    $selected = '';
    if ($field->getDataType() == $field->DATATYPE_TEXT && $field->isTextField()) {
        echo ' <option value="'. $field->getName() .'">'. $field->getLabel() .'</option>';
    }
}
echo '</select>';

?>
