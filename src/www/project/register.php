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
require_once('common/project/OneStepProjectCreationPresenter.class.php');
require_once 'vars.php'; //load licenses
require_once 'common/templating/TemplateRendererFactory.class.php';

$request      = HTTPRequest::instance();

if (Config::get('sys_create_project_in_one_step')) {
    $data = $request->params;
    $required_custom_descriptions = array();
    $res = db_query('SELECT * FROM group_desc WHERE desc_required = 1 ORDER BY desc_rank');
    while ($row = db_fetch_array($res)) {
        $required_custom_descriptions[$row['group_desc_id']] = new ProjectCustomDescription(
            $row['group_desc_id'],
            $row['desc_name'],
            $row['desc_description'],
            $row['desc_required'],
            $row['desc_type'],
            $row['desc_rank']
        );
    }
    $single_step_project = new OneStepProjectCreationPresenter($data, $current_user, $LICENSE, $required_custom_descriptions);
    if(isset($data['create_project']) && $single_step_project->validateAndGenerateErrors()) {
        $data    = $single_step_project->getProjectValues();
        if (! isset($data['project']['built_from_template'])) {
            $default_templates = $single_step_project->getDefaultTemplates();
            $data['project']['built_from_template'] = $default_templates->getGroupId();
        }
        $project = ProjectManager::instance()->getProject($data['project']['built_from_template']);
        foreach($project->services as $service) {
            $id = $service->getId();
            $data['project']['services'][$id]['is_used'] = $service->isUsed();
        }
        require_once('create_project.php');
        create_project($data);
    }

    $HTML->header(array('title'=> $Language->getText('register_index','project_registration')));

    $renderer  = TemplateRendererFactory::build()->getRenderer(Config::get('codendi_dir') .'/src/templates/project');
    $renderer->renderToPage('register', $single_step_project);

    $HTML->footer(array());
    exit; 
}

$current_step = $request->exist('current_step') ? $request->get('current_step') : 0;
$data         = $request->exist('data') ? unserialize($request->get('data')) : array();

//Register steps
if ($GLOBALS['sys_use_trove'] != 0) {
    $steps = array(
    new RegisterProjectStep_Intro($data),
    new RegisterProjectStep_Name($data),
    new RegisterProjectStep_Settings($data),
    new RegisterProjectStep_Template($data),
    new RegisterProjectStep_BasicInfo($data),
    new RegisterProjectStep_Services($data),
    new RegisterProjectStep_Category($data),
    new RegisterProjectStep_License($data),
    new RegisterProjectStep_Confirmation($data),
    );
} else {
    $steps = array(
    new RegisterProjectStep_Intro($data),
    new RegisterProjectStep_Name($data),
    new RegisterProjectStep_Settings($data),
    new RegisterProjectStep_Template($data),
    new RegisterProjectStep_BasicInfo($data),
    new RegisterProjectStep_Services($data),
    new RegisterProjectStep_License($data),
    new RegisterProjectStep_Confirmation($data),
    );
}

$csrf = new CSRFSynchronizerToken('/project/register.php');

//Process request
if ($request->exist('cancel')) {
    $HTML->addFeedback('info', $GLOBALS['Language']->getText('register_form', 'cancelled'));
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
            $csrf->check();
            
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
echo $csrf->fetchHTMLInput();
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
echo '<input type="hidden" name="data" value="'. htmlentities(serialize($data), ENT_QUOTES, 'UTF-8') .'" />';
echo '<input type="submit" name="next" id="project_register_next" value="'. ($current_step < count($steps) - 1 ? $GLOBALS['Language']->getText('register_form', 'next') : $GLOBALS['Language']->getText('register_title', 'intro')) .'" />';
echo '</td></tr>';
//{{{ Debug
//echo '<tr><td colspan="2"><pre>';var_dump($data);echo '</pre></td></tr>';
//echo '<tr><td colspan="2"><pre>';var_dump($_REQUEST);echo '</pre></td></tr>';
//}}}
echo '</table></form>';
$HTML->footer(array());
?>
