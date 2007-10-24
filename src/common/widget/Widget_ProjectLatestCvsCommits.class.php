<?php

require_once('Widget_ProjectLatestCommits.class.php');
require_once('www/cvs/commit_utils.php');

/**
* Widget_ProjectLatestCvsCommits
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectLatestCvsCommits extends Widget_ProjectLatestCommits {
    function Widget_ProjectLatestCvsCommits() {
        $this->Widget_ProjectLatestCommits('projectlatestcvscommits', 'cvs_get_revisions');
    }
    function getTitle() {
        return 'Latest CVS Commits';
    }
    function _getLinkToCommit($data) {
        return '/cvs/index.php?func=detailcommit&amp;group_id='.$this->group_id.'&amp;commit_id='.$data['commit_id'];
    }
    function _getLinkToMore() {
        return '/cvs/?func=browse&group_id='.$this->group_id;
    }
    function canBeUsedByProject(&$project) {
        return $project->usesCvs();
    }
    function isAvailable() {
        return $this->latest_revisions && user_isloggedin() ? true : false;
    }
}
?>
