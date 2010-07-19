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

        $user = UserManager::instance()->getCurrentUser();

        $result = db_query("SELECT groups.group_id, groups.group_name, groups.unix_group_name, groups.status, groups.is_public, user_group.admin_flags".
                           " FROM groups".
                           " JOIN user_group USING (group_id)".
                           " WHERE user_group.user_id = ".$user->getId().
                           " AND groups.status = 'A'".
                           " ORDER BY is_public, groups.group_name");
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $html .= $GLOBALS['Language']->getText('my_index', 'not_member');
        } else {
            $html .= '<table cellspacing="0" style="width:100%;">';
            $i     = 0;
            while ($row = db_fetch_array($result)) {
                $html .= '<tr class="'. util_get_alt_row_color($i++) .'" >';

                // Privacy
                if ($row['is_public'] == 0) {
                    $privacy = 'public';
                } else {
                    $privacy = 'private';
                }
                $html .= '<td style="padding-left: 0.5em; width: 1%;"><span class="project_privacy_'.$privacy.'">';
                $html .= $GLOBALS['Language']->getText('project_privacy', $privacy);
                $html .= '</span></td>';

                // Project name
                $html .= '<td style="padding-left: 0.5em; width: 50%;"><a href="/projects/'.$row['unix_group_name'].'/">'.$row['group_name'].'</a></td>';

                // Admin link
                $html .= '<td style="padding-left: 0.5em; text-align: left; font-size: smaller;">';
                if ($row['admin_flags'] == 'A') {
                    $html .= '<a href="/project/admin/?group_id='.$row['group_id'].'">['.$GLOBALS['Language']->getText('my_index', 'admin_link').']</a>';
                } else {
                    $html .= '&nbsp;';
                }
                $html .= '</td>';

                // Remove from project
                $html .= '<td style="padding-left: 0.5em; width: 1%; text-align: right;">';
                if ($row['admin_flags'] == 'A') {
                    $html .= '<a href="rmproject.php?group_id='.$row['group_id'].
                        '" onClick="return confirm(\''.$GLOBALS['Language']->getText('my_index', 'quit_proj').'\')">'.
                        '<img src="'.util_get_image_theme("ic/trash.png").'" height="16" width="16" border="0"></a>';
                } else {
                    $html .= '&nbsp;';
                }
                $html .= '</td>';

                $html .= '</tr>';
            }

            $html .= '</table>';

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