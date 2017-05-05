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
site_project_header(array('title'=>$title, 'group'=>$group_id, 'toptab'=>'summary', 'body_class' => array('widgetable')));


if ($project->getStatus() == 'H') {
	print '<p>'.$Language->getText('include_project_home','not_official_site',$GLOBALS['sys_name']).'</p>';
}

$lm = new WidgetLayoutManager();
$lm->displayLayout($project->getGroupId(), WidgetLayoutManager::OWNER_TYPE_GROUP);

site_project_footer(array());

?>
