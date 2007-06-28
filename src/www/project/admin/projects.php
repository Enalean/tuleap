<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');
$Language->loadLanguageMsg('project/project');

$request =& HTTPRequest::instance();
$group_id = $request->get('group_id');
if (!$group_id) {
    exit_missing_param();
} else {
    if (!$p =& project_get_object($group_id)) {
        exit_error($Language->getText('project_admin_index','invalid_p'),$Language->getText('project_admin_index','p_not_found'));
    } else {
        session_require(array('group' => $group_id, 'admin_flags' => 'A'));
        $display_headers_and_footers = !(isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&  $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
        if ($display_headers_and_footers) {
            project_admin_header(array(
                'title' => $Language->getText('project_admin_index','p_admin',group_getname($group_id)),
                'group' => $group_id,
                'help'  => 'ProjectAdministration.html'
            ));
            echo '<h2>'. $GLOBALS['Language']->getText('project_admin_index', 'show_projects') .'</h2>';
        }
        if (count($p->getProjectsCreatedFrom())) {
            echo '<ul>';
            $template =& TemplateSingleton::instance();
            $i = 0;
            foreach($p->getProjectsCreatedFrom() as $subproject) {
                echo '<li>';
                if ($template->isTemplate($subproject['type'])) {
                    echo '<b>';
                }
                echo '<a href="/projects/'. $subproject['unix_group_name'] .'">'. $subproject['group_name'] .'</a>';
                if ($template->isTemplate($subproject['type'])) {
                    echo '</b>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<em>'. $GLOBALS['Language']->getText('global', 'none') .'</em>';
        }
        if ($display_headers_and_footers) {
            project_admin_footer(array());
        }
    }
}
?>
