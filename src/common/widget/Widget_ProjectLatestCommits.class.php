<?php

require_once('Widget.class.php');
require_once('common/rss/RSS.class.php');

/**
* Widget_ProjectLatestCommits
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectLatestCommits extends Widget {
    var $latest_revisions;
    var $group_id;
    function Widget_ProjectLatestCommits($id, $get_commits_callback) {
        $this->Widget($id);
        $request =& HTTPRequest::instance();
        $project =& project_get_object($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            list($this->latest_revisions,) = $get_commits_callback($project, 0, 5);
            $this->group_id = $project->getGroupId();
        }
    }
    /* protected */ function _getLinkToCommit($data) { }
    /* protected */ function _getLinkToMore() { }
    
    function getContent() {
        $html = '';
        $i = 1;
        while($data = db_fetch_array($this->latest_revisions)) {
            $html .= '<div class="'. util_get_alt_row_color($i++) .'" style="border-bottom:1px solid #ddd">';
            $html .= '<div style="font-size:0.98em;">';
            $html .= '<a href="'. $this->_getLinkToCommit($data) .'">#'.$data['revision'].'</a>';
            $html .= ' by '.$data['who'] .' on ';
            //In the db, svn dates are stored as int whereas cvs dates are stored as timestamp
            $html .= format_date($GLOBALS['sys_datefmt'], (is_numeric($data['date']) ? $data['date'] : strtotime($data['date'])));
            $html .= '</div>';
            $html .= '<div style="padding-left:20px; padding-bottom:4px; color:#555">';
            $html .= util_make_links(substr($data['description'], 0, 255), $this->group_id);
            if (strlen($data['description']) > 255) {
                $html .= '&nbsp;[...]';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
        $html .= '<a href="'. $this->_getLinkToMore() .'">[ More ]</a>';
        $html .= '</div>';
        return $html;
    }
    function isAvailable() {
        return $this->latest_revisions && user_isloggedin() ? true : false;
    }
    function hasRss() {
        return false;
    }
    /*
    Do not use rss for this widget because we don't resolved authentification issue
    function displayRss() {
        $project =& project_get_object($this->group_id);
        $GLOBALS['Language']->loadLanguageMsg('rss/rss');
        $rss = new RSS(array(
            'title'       => $project->getPublicName() .' - '. $this->getTitle(),
            'description' => '',
            'link'        => get_server_url(),
            'language'    => 'en-us',
            'copyright'   => $GLOBALS['Language']->getText('rss','copyright',array($GLOBALS['sys_long_org_name'],$GLOBALS['sys_name'],date('Y',time()))),
            'pubDate'     => gmdate('D, d M Y G:i:s',time()).' GMT',
        ));
        while($data = db_fetch_array($this->latest_revisions)) {
            $rss->addItem(array(
                'title'       => '#'.$data['revision'] .' by '. $data['who'] .' on '. format_date($GLOBALS['sys_datefmt'], $data['date']),
                'description' => util_make_links(nl2br($data['description'])),
                'link'        => '/svn/?func=detailrevision&amp;group_id='.$this->group_id.'&amp;commit_id='.$data['commit_id']
            ));
        }
        $rss->display();
        exit;

    }*/
}
?>
