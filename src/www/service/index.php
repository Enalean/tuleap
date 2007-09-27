<?php

require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');

$request =& HTTPRequest::instance();

$project =& project_get_object($request->get('group_id'));
if ($project && $request->exist('id')) {
    $db_res = db_query("SELECT * 
        FROM service 
        WHERE group_id   = ". (int)$request->get('group_id') ."
          AND service_id = ". (int)$request->get('id') ." 
          AND is_used    = 1"
    );
    if (db_numrows($db_res) && $service = db_fetch_array($db_res)) {
        if ($service['is_in_iframe']) {
            $label = $service['label'];
            if ($label == "service_". $service['short_name'] ."_lbl_key") {
                $label = $Language->getText('project_admin_editservice',$label);
            } elseif(preg_match('/(.*):(.*)/', $label, $matches)) {
                $label = $Language->getText($matches[1], $matches[2]);
            }
            $title = $label .' - '. $project->getPublicName();
            site_project_header(array('title' => $title, 'group' => $request->get('group_id'), 'toptab' => $service['service_id']));
            $GLOBALS['HTML']->iframe($service['link'], array('class' => 'iframe_service'));
            site_project_footer(array());
        } else {
            $GLOBALS['Response']->redirect($service['link']);
        }
        exit();
    }
}
$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
$GLOBALS['Response']->redirect('/');
?>
