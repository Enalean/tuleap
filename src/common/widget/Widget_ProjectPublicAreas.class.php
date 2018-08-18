<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Widget\Event\GetPublicAreas;

require_once('Widget.class.php');

/**
* Widget_ProjectPublicAreas
*/
class Widget_ProjectPublicAreas extends Widget {

    public function __construct()
    {
        parent::__construct('projectpublicareas');
    }

    function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','public_areas');
    }
    function getContent() {
        $request = HTTPRequest::instance();
        $group_id = db_ei($request->get('group_id'));
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        $html = '';

        if ($project->usesHomePage()) {
            $html .= "<p><a ";
            if (substr($project->getHomePage(), 0, 1)!="/") {
                // Absolute link -> open new window on click
                $html .= 'target="_blank" rel="noreferrer" ';
            }
            $html .= 'href="' . $project->getHomePage() . '">';
            $html .= '<i class="tuleap-services-homepage tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home','proj_home').'</a></p>';
        }

        // ################## forums

        if ($project->usesForum()) {
            $html .= '<p><a href="'.$project->getForumPage().'">';
            $html .= '<i class="tuleap-services-forum tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home','public_forums').'</A>';

            $res_count = db_query("SELECT count(forum.msg_id) AS count FROM forum,forum_group_list WHERE "
                . "forum_group_list.group_id=" . db_ei($group_id) . " AND forum.group_forum_id=forum_group_list.group_forum_id "
                . "AND forum_group_list.is_public=1");
            $row_count = db_fetch_array($res_count);
            $pos = strpos($project->getForumPage(), '/forum/');
            if ($pos ===0) {
                $html .= ' ( '.$GLOBALS['Language']->getText('include_project_home','msg',$row_count['count']).' ';
                $res_count = db_query("SELECT count(*) AS count FROM forum_group_list WHERE group_id=" . db_ei($group_id) . " "
                . "AND is_public=1");
                $row_count = db_fetch_array($res_count);
                $html .= $GLOBALS['Language']->getText('include_project_home','forums',$row_count['count'])." )\n";
            }
            $html .= '</p>';
        }

        // ##################### Mailing lists (only for Active)

        if ($project->usesMail()) {
            $html .= '<p><a href="'.$project->getMailPage().'">';
            $html .= '<i class="tuleap-services-mail tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home','mail_lists').'</A>';
            $res_count = db_query("SELECT count(*) AS count FROM mail_group_list WHERE group_id=" . db_ei($group_id) . " AND is_public=1");
            $row_count = db_fetch_array($res_count);
            $html .= ' ( '.$GLOBALS['Language']->getText('include_project_home','public_mail_lists',$row_count['count']).' )</p>';
        }

        // ######################### Wiki (only for Active)

        if ($project->usesWiki()) {
            $html .= '<p><a href="'.$project->getWikiPage().'">';
            $html .= '<i class="tuleap-services-wiki tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home','wiki').'</A>';
                $wiki=new Wiki($group_id);
            $pos = strpos($project->getWikiPage(), '/wiki/');
            if ($pos === 0) {
                $html .= ' ( '.$GLOBALS['Language']->getText('include_project_home','nb_wiki_pages',$wiki->getProjectPageCount()).' )';
            }
            $html .= '</p>';
        }

        // ######################### CVS (only for Active)

        if ($project->usesCVS()) {
            $html .= '<p><a href="' . $project->getCvsPage() . '">';
            $html .= '<i class="tuleap-services-cvs tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'cvs_repo') . '</a>';
            // LJ Cvs checkouts added
            $sql = "SELECT SUM(cvs_commits) AS commits, SUM(cvs_adds) AS adds, SUM(cvs_checkouts) AS checkouts from stats_project where group_id='" . db_ei($group_id) . "'";
            $result = db_query($sql);
            $cvs_commit_num = db_result($result, 0, 0);
            $cvs_add_num = db_result($result, 0, 1);
            $cvs_co_num = db_result($result, 0, 2);
            if (!$cvs_commit_num)
                $cvs_commit_num = 0;
            if (!$cvs_add_num)
                $cvs_add_num = 0;
            if (!$cvs_co_num)
                $cvs_co_num = 0;
            $uri = session_make_url('/cvs/viewvc.php/?root=' . $project->getUnixName(false) . '&roottype=cvs');

            $html .= ' ( ' . $GLOBALS['Language']->getText('include_project_home', 'commits', $cvs_commit_num) . ', ' . $GLOBALS['Language']->getText('include_project_home', 'adds', $cvs_add_num) . ', ' . $GLOBALS['Language']->getText('include_project_home', 'co', $cvs_co_num) . ' )';
            if ($cvs_commit_num || $cvs_add_num || $cvs_co_num) {

                $html .= '<br> &nbsp; - <a href="' . $uri . '">' . $GLOBALS['Language']->getText('include_project_home', 'browse_cvs') . '</a>';
            }
            $html .= '</p>';
        }

        // ######################### Subversion (only for Active)

        if ($project->usesService('svn')) {
            $html .= '<p><a href="' . $project->getSvnPage() . '">';
            $html .= '<i class="tuleap-services-svn tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'svn_repo') . '</a>';
            $sql = "SELECT SUM(svn_access_count) AS accesses from group_svn_full_history where group_id='" . db_ei($group_id) . "'";
            $result = db_query($sql);
            $svn_accesses = db_result($result, 0, 0);
            if (!$svn_accesses)
                $svn_accesses = 0;

            $html .= ' ( ' . $GLOBALS['Language']->getText('include_project_home', 'accesses', $svn_accesses) . ' )';
            if ($svn_accesses) {
                $uri = session_make_url('/svn/viewvc.php/?root=' . $project->getUnixName(false) . '&roottype=svn');
                $html .= '<br> &nbsp; - <a href="' . $uri . '">' . $GLOBALS['Language']->getText('include_project_home', 'browse_svn') . '</a>';
            }
            $html .= '</p>';
        }

        // ######################### File Releases (only for Active)

        if ($project->usesFile()) {
            $html .= $project->getService(Service::FILE)->getPublicArea();
        }

        // ######################### Trackers (only for Active)
        if ( $project->usesTracker() ) {
            $html .= '<p><a href="'.$project->getTrackerPage().'">';
            $html .= '<i class="tuleap-services-tracker tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home','trackers').'</a>';
            //
            //  get the Group object
            //
            $pm = ProjectManager::instance();
            $group = $pm->getProject($group_id);
            if (!$group || !is_object($group) || $group->isError()) {
                exit_no_group();
            }
            $atf = new ArtifactTypeFactory($group);
            if (!$group || !is_object($group) || $group->isError()) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('include_project_home','no_arttypefact'));
            }

            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();

            if (!$at_arr || count($at_arr) < 1) {
                $html .= '<br><i>' . $GLOBALS['Language']->getText('include_project_home', 'no_trackers_accessible') . '</i>';
            } else {
                $html .= '<ul>';
                for ($j = 0; $j < count($at_arr); $j++) {
                    if ($at_arr[$j]->userCanView()) {
                        $html .= '<li>
                        <a href="/tracker/?atid=' . $at_arr[$j]->getID().'&group_id=' . $group_id . '&func=browse">' .
                        $at_arr[$j]->getName() . '</a></li>';
                    }
                }
                $html .= '</ul>';
            }
            $html .= '</p>';
        }


        // ######################## AnonFTP (only for Active)

        if ($project->isActive()) {
            $html .= '<p>';

            list($host) = explode(':',$GLOBALS['sys_default_domain']);
            if ($GLOBALS['sys_disable_subdomains']) {
            	$ftp_subdomain = "";
            } else {
            	$ftp_subdomain = $project->getUnixName() . ".";
            }
            $html .= "<a href=\"ftp://" . $ftp_subdomain . $host ."/pub/". $project->getUnixName(false) ."/\">";    // keep the first occurence in lower case
            $html .= '<i class="tuleap-services-ftp tuleap-services-widget"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home','anon_ftp_space').'</a>';
            $html .= '</p>';
        }

        $event = new GetPublicAreas($project);
        EventManager::instance()->processEvent($event);
        foreach($event->getAreas() as $area) {
            $html .= '<p>'.$area.'</p>';
        }

        return $html;
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_public_areas','description');
    }
}
