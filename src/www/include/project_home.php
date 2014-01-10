<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('vars.php');
require_once('www/news/news_utils.php');
require_once('trove.php');
require_once('www/project/admin/permissions.php');
require_once('common/wiki/lib/Wiki.class.php');
require_once 'common/templating/TemplateRendererFactory.class.php';
require_once 'common/mail/MassmailFormPresenter.class.php';
require_once 'common/include/CSRFSynchronizerToken.class.php';

$hp =& Codendi_HTMLPurifier::instance();

$title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();

require_once 'common/user/User.class.php';
$uM = UserManager::instance();
$user = $uM->getCurrentUser();
require_once('www/include/trove.php');
if ($GLOBALS['sys_trove_cat_mandatory'] && $user->isMember($group_id, 'A') && !trove_project_categorized($group_id) && substr($_SERVER['SCRIPT_NAME'],0,9) == '/projects') {
    $trove_url = '/project/admin/group_trove.php?group_id='.$group_id;
    $GLOBALS['Response']->addFeedback('warning',$GLOBALS['Language']->getText('include_html','no_trovcat',array($trove_url)), CODENDI_PURIFIER_DISABLED);
}
site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));


if ($project->getStatus() == 'H') {
	print '<p>'.$Language->getText('include_project_home','not_official_site',$GLOBALS['sys_name']).'</p>';
}

$token     = new CSRFSynchronizerToken('');
$presenter = new MassmailFormPresenter(
    $group_id,
    $token,
    $GLOBALS['Language']->getText('contact_admins','title', array($project->getPublicName())),
    '/include/massmail_to_project_admins.php'
);
$template_factory = TemplateRendererFactory::build();
$renderer         = $template_factory->getRenderer($presenter->getTemplateDir());

echo '<a href="#massmail_'.$group_id.'" class="project_home_contact_admins" data-toggle="modal">'. $GLOBALS['Language']->getText('include_project_home', 'contact_admins') .'</a>';
echo $renderer->renderToString('massmail', $presenter);
echo '<br />';
echo '<br />';

$lm = new WidgetLayoutManager();
$lm->displayLayout($project->getGroupId(), WidgetLayoutManager::OWNER_TYPE_GROUP);

site_project_footer(array());

?>