<?php

require_once('Widget.class.php');
require_once('common/rss/RSS.class.php');

/**
* Widget_ProjectLatestSvnCommits
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectLatestSvnCommits extends Widget {
    var $latest_revisions;
    var $group_id;
    function Widget_ProjectLatestSvnCommits() {
        $this->Widget('projectlatestsvncommits');
        $request =& HTTPRequest::instance();
        $project =& project_get_object($request->get('group_id'));
        if ($project && $project->usesFile()) {
            require_once('www/svn/svn_utils.php');
            $path = '';
            list($this->latest_revisions,) = svn_get_revisions($project, 0, 5, $path);
            $this->group_id = $project->getGroupId();
        }
    }
    function getTitle() {
        return 'Latest SVN Commits';
    }
    function getContent() {
        $html = '';
        $i = 1;
        while($data = db_fetch_array($this->latest_revisions)) {
            $html .= '<div class="'. util_get_alt_row_color($i++) .'" style="border-bottom:1px solid #ddd">';
            $html .= '<div style="font-size:0.98em;">';
            $html .= '<a href="/svn/?func=detailrevision&amp;group_id='.$this->group_id.'&amp;commit_id='.$data['commit_id'].'">#'.$data['revision'].'</a>';
            $html .= ' by '.$data['who'] .' on '. format_date($GLOBALS['sys_datefmt'], $data['date']);
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
        $html .= '<a href="/svn/?func=browse&group_id='. $this->group_id .'">[ More ]</a>';
        $html .= '</div>';
        return $html;
    }
    function isAvailable() {
        return $this->latest_revisions ? true : false;
    }
    function hasRss() {
        return true;
    }
    function displayRss() {
        $GLOBALS['Language']->loadLanguageMsg('rss/rss');
        $rss = new RSS(array(
            'title'       => $this->getTitle(),
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

    }
}
?>
