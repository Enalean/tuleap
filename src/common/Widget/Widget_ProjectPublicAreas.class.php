<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

/**
* Widget_ProjectPublicAreas
*/
class Widget_ProjectPublicAreas extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct()
    {
        parent::__construct('projectpublicareas');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home', 'public_areas');
    }

    public function getContent()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $request  = HTTPRequest::instance();
        $group_id = db_ei($request->get('group_id'));
        $pm       = ProjectManager::instance();
        $project  = $pm->getProject($group_id);
        $html     = '';

        $homepage_service = $project->getService(Service::HOMEPAGE);
        if ($homepage_service !== null) {
            $html .= "<p><a ";
            if (substr($homepage_service->getUrl(), 0, 1) != "/") {
                // Absolute link -> open new window on click
                $html .= 'target="_blank" rel="noreferrer" ';
            }
            $html .= 'href="' . $purifier->purify($homepage_service->getUrl()) . '">';
            $html .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($homepage_service->getIcon()) . '"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'proj_home') . '</a></p>';
        }

        // ################## forums

        $service_forum = $project->getService(Service::FORUM);
        if ($service_forum !== null) {
            $html .= '<p><a href="' . $purifier->purify($service_forum->getUrl()) . '">';
            $html .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($service_forum->getIcon()) . '"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'public_forums') . '</A>';

            $res_count = db_query("SELECT count(forum.msg_id) AS count FROM forum,forum_group_list WHERE "
                . "forum_group_list.group_id=" . db_ei($group_id) . " AND forum.group_forum_id=forum_group_list.group_forum_id "
                . "AND forum_group_list.is_public=1");
            $row_count = db_fetch_array($res_count);
            $pos       = strpos($project->getForumPage(), '/forum/');
            if ($pos === 0) {
                $html     .= ' ( ' . $GLOBALS['Language']->getText('include_project_home', 'msg', $row_count['count']) . ' ';
                $res_count = db_query("SELECT count(*) AS count FROM forum_group_list WHERE group_id=" . db_ei($group_id) . " "
                . "AND is_public=1");
                $row_count = db_fetch_array($res_count);
                $html     .= $GLOBALS['Language']->getText('include_project_home', 'forums', $row_count['count']) . " )\n";
            }
            $html .= '</p>';
        }

        // ######################### Wiki (only for Active)

        $wiki_service = $project->getService(Service::WIKI);
        if ($wiki_service !== null) {
            $html    .= '<p><a href="' . $purifier->purify($wiki_service->getUrl()) . '">';
            $html    .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($wiki_service->getIcon()) . '"></i>';
            $html    .= $GLOBALS['Language']->getText('include_project_home', 'wiki') . '</A>';
                $wiki = new Wiki($group_id);
            $pos      = strpos($project->getWikiPage(), '/wiki/');
            if ($pos === 0) {
                $html .= ' ( ' . $GLOBALS['Language']->getText('include_project_home', 'nb_wiki_pages', $wiki->getProjectPageCount()) . ' )';
            }
            $html .= '</p>';
        }

        // ######################### CVS (only for Active)

        $cvs_service = $project->getService(Service::CVS);
        if ($cvs_service !== null) {
            $html .= '<p><a href="' . $purifier->purify($cvs_service->getUrl()) . '">';
            $html .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($cvs_service->getIcon()) . '"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'cvs_repo') . '</a>';
            $html .= '</p>';
        }

        // ######################### Subversion (only for Active)

        $svn_service = $project->getService(Service::SVN);
        if ($svn_service !== null) {
            $html        .= '<p><a href="' . $purifier->purify($svn_service->getUrl()) . '">';
            $html        .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($svn_service->getIcon()) . '"></i>';
            $html        .= $GLOBALS['Language']->getText('include_project_home', 'svn_repo') . '</a>';
            $sql          = "SELECT SUM(svn_access_count) AS accesses from group_svn_full_history where group_id='" . db_ei($group_id) . "'";
            $result       = db_query($sql);
            $svn_accesses = db_result($result, 0, 0);
            if (! $svn_accesses) {
                $svn_accesses = 0;
            }

            $html .= ' ( ' . $GLOBALS['Language']->getText('include_project_home', 'accesses', $svn_accesses) . ' )';
            if ($svn_accesses) {
                $uri   = session_make_url('/svn/viewvc.php/?root=' . urlencode($project->getUnixName(false)) . '&roottype=svn');
                $html .= '<br> &nbsp; - <a href="' . $purifier->purify($uri) . '">' . $GLOBALS['Language']->getText('include_project_home', 'browse_svn') . '</a>';
            }
            $html .= '</p>';
        }

        // ######################### File Releases (only for Active)

        $file_service = $project->getService(Service::FILE);
        if ($file_service !== null) {
            $html .= $file_service->getPublicArea();
        }

        // ######################### Trackers (only for Active)
        $trackerv3_service = $project->getService(Service::TRACKERV3);
        if ($trackerv3_service !== null) {
            $html .= '<p><a href="' . $purifier->purify($trackerv3_service->getUrl()) . '">';
            $html .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($trackerv3_service->getIcon()) . '"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'trackers') . '</a>';
            //  get the Group object
            $pm    = ProjectManager::instance();
            $group = $pm->getProject($group_id);
            if (! $group || ! is_object($group) || $group->isError()) {
                exit_no_group();
            }
            $atf = new ArtifactTypeFactory($group);
            if (! $group || ! is_object($group) || $group->isError()) {
                exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('include_project_home', 'no_arttypefact'));
            }

            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();

            if (! $at_arr || count($at_arr) < 1) {
                $html .= '<br><i>' . $GLOBALS['Language']->getText('include_project_home', 'no_trackers_accessible') . '</i>';
            } else {
                $html .= '<ul>';
                for ($j = 0; $j < count($at_arr); $j++) {
                    if ($at_arr[$j]->userCanView()) {
                        $html .= '<li>
                        <a href="/tracker/?atid=' . urlencode($at_arr[$j]->getID()) . '&group_id=' . urlencode($group_id) . '&func=browse">' .
                            $purifier->purify($at_arr[$j]->getName()) . '</a></li>';
                    }
                }
                $html .= '</ul>';
            }
            $html .= '</p>';
        }

        // ######################## AnonFTP (only for Active)

        if ($project->isActive()) {
            $html .= '<p>';

            $host = \Tuleap\ServerHostname::rawHostname();
            if (ForgeConfig::get('sys_disable_subdomains')) {
                $ftp_subdomain = "";
            } else {
                $ftp_subdomain = $project->getUnixName() . ".";
            }
            $html .= "<a href=\"ftp://" . $ftp_subdomain . $host . "/pub/" . urlencode($project->getUnixName(false)) . "/\">";    // keep the first occurence in lower case
            $html .= '<i class="dashboard-widget-content-projectpublicareas fa fa-tlp-folder-globe"></i>';
            $html .= $GLOBALS['Language']->getText('include_project_home', 'anon_ftp_space') . '</a>';
            $html .= '</p>';
        }

        $event = new GetPublicAreas($project);
        EventManager::instance()->processEvent($event);
        foreach ($event->getAreas() as $area) {
            $html .= '<p>' . $area . '</p>';
        }

        return $html;
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_public_areas', 'description');
    }
}
