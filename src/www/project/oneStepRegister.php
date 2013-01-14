<?php

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once('pre.php');
session_require(array('isloggedin'=>1));

require_once('common/include/HTTPRequest.class.php');
require_once('common/include/CSRFSynchronizerToken.class.php');

require_once('common/project/RegisterProjectStep_Intro.class.php');
require_once('common/project/RegisterProjectStep_Settings.class.php');
require_once('common/project/RegisterProjectStep_Template.class.php');
require_once('common/project/RegisterProjectStep_BasicInfo.class.php');
require_once('common/project/RegisterProjectStep_Name.class.php');
require_once('common/project/RegisterProjectStep_License.class.php');
require_once('common/project/RegisterProjectStep_Category.class.php');
require_once('common/project/RegisterProjectStep_Confirmation.class.php');
require_once('common/project/RegisterProjectStep_Services.class.php');
require_once('common/project/RegisterProjectOneStep.class.php');

$request      = HTTPRequest::instance();
$current_step = $request->exist('current_step') ? $request->get('current_step') : 0;
$data         = $request->exist('data') ? unserialize($request->get('data')) : array();

//Display current step
$HTML->header(array('title'=>$Language->getText('register_index','project_registration')));

if ($request->exist('onestep')) {
    $single_step = new RegisterProjectOneStep($data);
    $single_step->display($data);
}
$HTML->footer(array());
?>
