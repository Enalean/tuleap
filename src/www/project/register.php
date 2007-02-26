<?php
require_once('pre.php');
session_require(array('isloggedin'=>1));

require_once('common/include/HTTPRequest.class.php');

require_once('common/project/RegisterProjectStep_Intro.class.php');
require_once('common/project/RegisterProjectStep_Template.class.php');
require_once('common/project/RegisterProjectStep_BasicInfo.class.php');
require_once('common/project/RegisterProjectStep_Name.class.php');
require_once('common/project/RegisterProjectStep_License.class.php');
require_once('common/project/RegisterProjectStep_Category.class.php');
require_once('common/project/RegisterProjectStep_Confirmation.class.php');
require_once('common/project/RegisterProjectStep_Services.class.php');

$Language->loadLanguageMsg('project/project');
$Language->loadLanguageMsg('project/register');

$request =& HTTPRequest::instance();
$current_step = $request->exist('current_step') ? $request->get('current_step') : 0;
$data         = $request->exist('data') ? unserialize($request->get('data')) : array();

//Register steps
$steps = array(
    new RegisterProjectStep_Intro($data),
    new RegisterProjectStep_Name($data),
    new RegisterProjectStep_Template($data),
    new RegisterProjectStep_BasicInfo($data),
    new RegisterProjectStep_Services($data),
    new RegisterProjectStep_Category($data),
    new RegisterProjectStep_License($data),
    new RegisterProjectStep_Confirmation($data),
);

//Process request
if ($request->exist('cancel')) {
    $HTML->addFeedback('info', 'Project creation cancelled');
    $HTML->redirect('/');
}
if ($request->exist('next') && $steps[$current_step]->onLeave($request, $data) && (!isset($steps[$current_step + 1]) || $steps[$current_step + 1]->onEnter($request, $data))) {
    $current_step++;
    if ($current_step == count($steps)) {
        //We finish wizard, do a final validation
        $is_valid = true;
        reset($steps);
        while($is_valid && list($key,) = each($steps)) {
            $is_valid = $steps[$key]->validate($data);
        }
        if (!$is_valid) {
            $current_step--;
        } else {
            require_once('create_project.php');
            create_project($data);
        }
    }
}
if ($request->exist('previous')) {
    $current_step--;
}

//Display current step
$HTML->header(array('title'=>$Language->getText('register_index','project_registration') .' - '. $steps[$current_step]->name));
echo '<style>
.current_step {
    font-weight:bold;
}
.next_step {
    color:#999;
}
</style>';
echo '<form action="" method="POST">';
echo '<table>';
echo '<tr style="vertical-align:top;">';

echo '<td>';
echo '<h2>'. $steps[$current_step]->name .' ';
echo help_button($steps[$current_step]->help);
echo '</h2>';
$steps[$current_step]->display($data);
echo '</td>';

echo '<td rowspan="2"><ol>';
foreach($steps as $key => $step) {
    $classname = $key == $current_step ? 'current_step' : ($key < $current_step ? 'previous_step' : 'next_step');
    echo '<li class="'. $classname .'">'. $step->name .'</li>';
}
echo '</ol></td>';

echo '</tr><tr>';
echo '<td style="text-align:center">';
echo '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('register_form', 'cancel') .'" /> ';
echo '<input type="hidden" name="current_step" value="'. $current_step .'" />';
echo '<input type="hidden" name="data" value="'. htmlentities(serialize($data), ENT_QUOTES) .'" />';
echo '<input type="submit" name="next" id="project_register_next" value="'. ($current_step < count($steps) - 1 ? $GLOBALS['Language']->getText('register_form', 'next') : $GLOBALS['Language']->getText('register_title', 'intro')) .'" />';
echo '</td></tr>';
//{{{ Debug
//echo '<tr><td colspan="2"><pre>';var_dump($data);echo '</pre></td></tr>';
//echo '<tr><td colspan="2"><pre>';var_dump($_REQUEST);echo '</pre></td></tr>';
//}}}
echo '</table></form>';
$HTML->footer(array());
?>
