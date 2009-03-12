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
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');
require_once('common/wiki/lib/Wiki.class.php');
require_once('www/project/admin/permissions.php');
require_once('common/event/EventManager.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/include/CodeX_HTMLPurifier.class.php');

$hp =& CodeX_HTMLPurifier::instance();

$title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();

$HTML->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');

site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));


if ($project->getStatus() == 'H') {
	print '<P>'.$Language->getText('include_project_home','not_official_site',$GLOBALS['sys_name']);
}


$lm->displayLayout($project->getGroupId(), WidgetLayoutManager::OWNER_TYPE_GROUP);

site_project_footer(array());

?>