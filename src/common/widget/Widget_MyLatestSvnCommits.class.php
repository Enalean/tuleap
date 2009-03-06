<?php

/**
* Widget_MyLatestSvnCommits
* 
* Copyright (c) Xerox Corporation, Codendi 2001-2009.
*
* @author  marc.nazarian@xrce.xerox.com
*/
class Widget_MyLatestSvnCommits extends Widget {
    
    const NB_COMMITS_TO_DISPLAY = 5;
    
    public function __construct() {
        $this->Widget('mylatestsvncommits');
    }
    public function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','my_latest_svn_commit');
    }
    public function _getLinkToCommit($group_id, $commit_id) {
        return '/svn/?func=detailrevision&amp;group_id='.$group_id.'&amp;commit_id='.$commit_id;
    }
    public function _getLinkToMore($group_id, $commiter) {
        return '/svn/?func=browse&group_id='.$group_id.'&_commiter='.$commiter;
    }
    
    public function getContent() {
        $html = '';
        $uh = new UserHelper();
        $hp = CodeX_HTMLPurifier::instance();
        $user = UserManager::instance()->getCurrentUser();
        $project_ids = $user->getProjects();
        foreach ($project_ids as $project_id) {
            $project = new Project($project_id);
            if ($project->usesSVN()) {
                list($latest_revisions, $nb_revisions) = svn_get_revisions($project, 0, self::NB_COMMITS_TO_DISPLAY, '', $user->getUserName());
                if ($nb_revisions > 0) {
                    $html .= '<h4>' . $project->getPublicName() . '</h4>';
                    $i = 0;
                    while ($data = db_fetch_array($latest_revisions)) {
                        $html .= '<div class="'. util_get_alt_row_color($i++) .'" style="border-bottom:1px solid #ddd">';
                        $html .= '<div style="font-size:0.98em;">';
                        $html .= '<a href="'. $this->_getLinkToCommit($project->getGroupId(), $data['revision']) .'">rev #'.$data['revision'].'</a>';
                        $html .= ' '.$GLOBALS['Language']->getText('include_project_home','my_latest_svn_commit_on').' ';
                        //In the db, svn dates are stored as int whereas cvs dates are stored as timestamp
                        $html .= format_date($GLOBALS['Language']->getText('system', 'datefmt'), (is_numeric($data['date']) ? $data['date'] : strtotime($data['date'])));
                        $html .= ' '.$GLOBALS['Language']->getText('include_project_home','my_latest_svn_commit_by').' ';
                        $html .= $hp->purify($uh->getDisplayNameFromUserName($data['who']), CODEX_PURIFIER_CONVERT_HTML);
                        $html .= '</div>';
                        $html .= '<div style="padding-left:20px; padding-bottom:4px; color:#555">';
                        $html .= util_make_links(substr($data['description'], 0, 255), $project->getGroupId());
                        if (strlen($data['description']) > 255) {
                            $html .= '&nbsp;[...]';
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    $html .= '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
                    $html .= '<a href="'. $this->_getLinkToMore($project->getGroupId(), $user->getUserName()) .'">[ More ]</a>';
                    $html .= '</div>';
                    return $html;
                }
            }
        }
    }
}
?>