<?php

require_once('Widget_ProjectLatestCommits.class.php');
require_once('www/svn/svn_utils.php');

/**
* Widget_ProjectLatestSvnCommits
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectLatestSvnCommits extends Widget_ProjectLatestCommits {
    function Widget_ProjectLatestSvnCommits() {
        $this->Widget_ProjectLatestCommits('projectlatestsvncommits', 'svn_get_revisions');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','latest_svn_commit');
    }
    function _getLinkToCommit($data) {
        return '/svn/?func=detailrevision&amp;group_id='.$this->group_id.'&amp;commit_id='.$data['commit_id'];
    }
    function _getLinkToMore() {
        return '/svn/?func=browse&group_id='.$this->group_id;
    }
    function canBeUsedByProject(&$project) {
        return $project->usesSvn();
    }
    function getPreviewCssClass() {
        return parent::getPreviewCssClass('project_latest_svn_commits');
    }
}
?>
