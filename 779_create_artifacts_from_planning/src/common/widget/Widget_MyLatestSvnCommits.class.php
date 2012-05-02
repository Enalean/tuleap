<?php

/**
* Widget_MyLatestSvnCommits
* 
* Copyright (c) Xerox Corporation, Codendi 2001-2009.
*
* @author  marc.nazarian@xrce.xerox.com
*/
class Widget_MyLatestSvnCommits extends Widget {
    
    /**
     * Default number of SVN commits to display (if user did not change/set preferences) 
     */
    const NB_COMMITS_TO_DISPLAY = 5;
    
    /**
     * Number of SVN commits to display (user preferences) 
     */
    private $_nb_svn_commits;
    
    public function __construct() {
        $this->Widget('mylatestsvncommits');
        $this->_nb_svn_commits = user_get_preference('my_latests_svn_commits_nb_display');
        if($this->_nb_svn_commits === false) {
            $this->_nb_svn_commits = self::NB_COMMITS_TO_DISPLAY;
            user_set_preference('my_latests_svn_commits_nb_display', $this->_nb_svn_commits);
        }
    }
    public function getTitle() {
        return $GLOBALS['Language']->getText('my_index','my_latest_svn_commit');
    }
    public function _getLinkToCommit($group_id, $commit_id) {
        return '/svn/?func=detailrevision&amp;group_id='.$group_id.'&amp;rev_id='.$commit_id;
    }
    public function _getLinkToMore($group_id, $commiter) {
        return '/svn/?func=browse&group_id='.$group_id.'&_commiter='.$commiter;
    }
    
    public function getContent() {
        $html        = '';
        $uh          = UserHelper::instance();
        $request     = HTTPRequest::instance();
        $hp          = Codendi_HTMLPurifier::instance();
        $user        = UserManager::instance()->getCurrentUser();
        $pm          = ProjectManager::instance();
        $project_ids = $user->getProjects();
        foreach ($project_ids as $project_id) {
            $project = $pm->getProject($project_id);
            if ($project->usesSVN()) {
                list($hide_now,$count_diff,$hide_url) = my_hide_url('my_svn_group', $project_id, $request->get('hide_item_id'), count($project_ids), $request->get('hide_my_svn_group'));
                $html .= $hide_url;

                $html .= '<strong>' . $project->getPublicName() . '</strong>';
                if (!$hide_now) {
                    list($latest_revisions, $nb_revisions) = svn_get_revisions($project, 0, $this->_nb_svn_commits, '', $user->getUserName(), '', '', 0, false);
                    if (db_numrows($latest_revisions) > 0) {
                        $i = 0;
                        while ($data = db_fetch_array($latest_revisions)) {
                            $html .= '<div class="'. util_get_alt_row_color($i++) .'" style="border-bottom:1px solid #ddd">';
                            $html .= '<div style="font-size:0.98em;">';
                            $html .= '<a href="'. $this->_getLinkToCommit($project->getGroupId(), $data['revision']) .'">rev #'.$data['revision'].'</a>';
                            $html .= ' '.$GLOBALS['Language']->getText('my_index','my_latest_svn_commit_on').' ';
                            //In the db, svn dates are stored as int whereas cvs dates are stored as timestamp
                            $html .= format_date($GLOBALS['Language']->getText('system', 'datefmt'), (is_numeric($data['date']) ? $data['date'] : strtotime($data['date'])));
                            $html .= ' '.$GLOBALS['Language']->getText('my_index','my_latest_svn_commit_by').' ';
                            if (isset($data['whoid'])) {
                                $name = $uh->getDisplayNameFromUserId($data['whoid']);
                            } else {
                                $name = $uh->getDisplayNameFromUserName($data['who']);
                            }
                            $html .= $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML);
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
                    } else {
                        $html .= '<div></div>';
                    }
                } else {
                    $html .= '<div></div>';
                }
            }
        }
        return $html;
    }
    function getPreferences() {
        $prefs  = '';
        $prefs .= $GLOBALS['Language']->getText('my_index', 'my_latest_svn_commit_nb_prefs');
        $prefs .= ' <input name="nb_svn_commits" type="text" size="2" maxlenght="3" value="'.user_get_preference('my_latests_svn_commits_nb_display').'">';
        return $prefs;
    }
    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        $nbShow = new Valid_UInt('nb_svn_commits');
        $nbShow->required();
        if (!$request->exist('cancel')) {
            if ($request->valid($nbShow)) {
                $this->_nb_svn_commits = $request->get('nb_svn_commits');
            } else {
                $this->_nb_svn_commits = self::NB_COMMITS_TO_DISPLAY;
            }
            user_set_preference('my_latests_svn_commits_nb_display', $this->_nb_svn_commits);
        }
        return true;
    }
    
    function getCategory() {
        return 'scm';
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_my_latest_svn_commits','description');
    }
    function isAjax() {
        return true;
    }
    function getAjaxUrl($owner_id, $owner_type) {
        $request =& HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type);
        if ($request->exist('hide_item_id') || $request->exist('hide_my_svn_group')) {
            $ajax_url .= '&hide_item_id=' . $request->get('hide_item_id') . '&hide_my_svn_group=' . $request->get('hide_my_svn_group');
        }
        return $ajax_url;
    }
}
?>
