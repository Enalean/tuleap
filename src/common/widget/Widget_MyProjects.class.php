<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Widget.class.php');
require_once('common/rss/RSS.class.php');

/**
* Widget_MyProjects
* 
* PROJECT LIST
*/
class Widget_MyProjects extends Widget {
    function Widget_MyProjects() {
        $this->Widget('myprojects');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_projects');
    }
    function getContent() {
        $html = '';
        $display_privacy = Config::get('sys_display_project_privacy_in_service_bar');
        $user = UserManager::instance()->getCurrentUser();

        $order = 'groups.group_name';
        if ($display_privacy) {
            $order = 'is_public, groups.group_name';
        }
        $result = db_query("SELECT groups.group_id, groups.group_name, groups.unix_group_name, groups.status, groups.is_public, user_group.admin_flags".
                           " FROM groups".
                           " JOIN user_group USING (group_id)".
                           " WHERE user_group.user_id = ".$user->getId().
                           " AND groups.status = 'A'".
                           " ORDER BY $order");
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $html .= $GLOBALS['Language']->getText('my_index', 'not_member');
        } else {
            $html .= '<table cellspacing="0" class="widget_my_projects">';
            $i     = 0;
            $prevIsPublic = -1;
            while ($row = db_fetch_array($result)) {
                $tdClass = '';
                if ($display_privacy && $prevIsPublic == 0 && $row['is_public'] == 1) {
                    $tdClass .= ' widget_my_projects_first_public';
                }

                $html .= '<tr class="'.util_get_alt_row_color($i++).'" >';

                // Privacy
                if ($display_privacy) {
                    if ($row['is_public'] == 1) {
                        $privacy = 'public';
                    } else {
                        $privacy = 'private';
                    }
                    $html .= '<td class="widget_my_projects_privacy'.$tdClass.'"><span class="project_privacy_'.$privacy.'">';
                    $html .= '&nbsp;';
                    $html .= '</span></td>';
                }

                // Project name
                $html .= '<td class="widget_my_projects_project_name'.$tdClass.'"><a href="/projects/'.$row['unix_group_name'].'/">'.$row['group_name'].'</a></td>';

                // Admin link
                $html .= '<td class="widget_my_projects_actions'.$tdClass.'">';
                if ($row['admin_flags'] == 'A') {
                    $html .= '<a href="/project/admin/?group_id='.$row['group_id'].'">['.$GLOBALS['Language']->getText('my_index', 'admin_link').']</a>';
                } else {
                    $html .= '&nbsp;';
                }
                $html .= '</td>';

                // Remove from project
                $html .= '<td class="widget_my_projects_remove'.$tdClass.'">';
                if ($row['admin_flags'] != 'A') {
                    $html .= html_trash_link('rmproject.php?group_id='.$row['group_id'], $GLOBALS['Language']->getText('my_index', 'quit_proj'), $GLOBALS['Language']->getText('my_index', 'quit_proj'));
                } else {
                    $html .= '&nbsp;';
                }
                $html .= '</td>';

                $html .= '</tr>';

                $prevIsPublic = $row['is_public'];
            }

            if ($display_privacy) {
                // Legend
                $html .= '<tr>';
                $html .= '<td colspan="4" class="widget_my_projects_legend">';
                $html .= '<span class="widget_my_projects_legend_title">'.$GLOBALS['Language']->getText('my_index', 'my_projects_legend').'</span>';
                $html .= '<span class="project_privacy_private">&nbsp;'.$GLOBALS['Language']->getText('project_privacy', 'private').'</span>';
                $html .= '<span class="project_privacy_public">&nbsp;'.$GLOBALS['Language']->getText('project_privacy', 'public').'</span>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';

            if ($display_privacy) {
                // Javascript for project privacy tooltip
                $js = "
                    document.observe('dom:loaded', function() {
                        $$('span[class=project_privacy_private], span[class=project_privacy_public]').each(function (span) {
                            var type = span.className.substring('project_privacy_'.length, span.className.length);
                            codendi.Tooltips.push(new codendi.Tooltip(span, '/project/privacy.php?project_type='+type));
                        });
                    });
                ";
                $GLOBALS['HTML']->includeFooterJavascriptSnippet($js);
            }
        }
        return $html;
    }
    function hasRss() {
        return true;
    }
    function displayRss() {
        $rss = new RSS(array(
            'title'       => 'Codendi - MyProjects',
            'description' => 'My projects',
            'link'        => get_server_url(),
            'language'    => 'en-us',
            'copyright'   => 'Copyright Xerox',
            'pubDate'     => gmdate('D, d M Y G:i:s',time()).' GMT',
        ));
        $result = db_query("SELECT groups.group_name,"
            . "groups.group_id,"
            . "groups.unix_group_name,"
            . "groups.status,"
            . "groups.is_public,"
            . "user_group.admin_flags "
            . "FROM groups,user_group "
            . "WHERE groups.group_id=user_group.group_id "
            . "AND user_group.user_id='". user_getid() ."' "
            . "AND groups.status='A' ORDER BY group_name");
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $rss->addItem(array(
                'title'       => 'Error',
                'description' => $GLOBALS['Language']->getText('my_index', 'not_member') . db_error(),
                'link'        => get_server_url()
            ));
        } else {
            for ($i=0; $i<$rows; $i++) {
                $title = db_result($result,$i,'group_name');
                if ( db_result($result,$i,'is_public') == 0 ) {
                    $title .= ' (*)';
                }
                
                $desc = 'Project: '. get_server_url() .'/project/admin/?group_id='.db_result($result,$i,'group_id') ."<br />\n";
                if ( db_result($result,$i,'admin_flags') == 'A' ) {
                    $desc .= 'Admin: '. get_server_url() .'/project/admin/?group_id='.db_result($result,$i,'group_id');
                }
                
                $rss->addItem(array(
                    'title'       => $title,
                    'description' => $desc,
                    'link'        => get_server_url() .'/projects/'. db_result($result,$i,'unix_group_name')
                ));
            }
        }
        $rss->display();
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_my_projects','description');
    }
}
?>