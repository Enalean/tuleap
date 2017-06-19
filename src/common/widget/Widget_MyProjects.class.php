<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

require_once('Widget.class.php');
require_once('common/rss/RSS.class.php');
require_once 'common/templating/TemplateRendererFactory.class.php';
require_once 'common/mail/MassmailFormPresenter.class.php';

/**
* Widget_MyProjects
*
* PROJECT LIST
*/
class Widget_MyProjects extends Widget {

    public function __construct()
    {
        parent::__construct('myprojects');
    }

    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_projects');
    }

    public function getContent()
    {
        return $this->getWidgetContent(true);
    }

    public function getContentForBurningParrot()
    {
        $GLOBALS['HTML']->includeFooterJavascriptFile("/scripts/ckeditor-4.3.2/ckeditor.js");
        $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');
        $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/widgets/contact-modal.js');

        return $this->getWidgetContent(false);
    }

    private function getWidgetContent($use_legacy_template)
    {
        $html = '';
        $display_privacy = ForgeConfig::get('sys_display_project_privacy_in_service_bar');
        $user = UserManager::instance()->getCurrentUser();

        $order = 'groups.group_name';
        if ($display_privacy) {
            $order = 'access, groups.group_name';
        }
        $result = db_query("SELECT groups.group_id, groups.group_name, groups.unix_group_name, groups.status, groups.access, user_group.admin_flags".
                           " FROM groups".
                           " JOIN user_group USING (group_id)".
                           " WHERE user_group.user_id = ". db_ei($user->getId()).
                           " AND groups.status = 'A'".
                           " ORDER BY $order");
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $html .= $GLOBALS['Language']->getText('my_index', 'not_member');
        } else {
            $html .= '<table cellspacing="0" class="tlp-table widget_my_projects">';
            $i     = 0;
            $prevIsPublic = -1;
            $token = new CSRFSynchronizerToken('massmail_to_project_members.php');
            while ($row = db_fetch_array($result)) {
                $tdClass = '';
                if ($display_privacy && $prevIsPublic == 0 && $row['access'] != Project::ACCESS_PRIVATE) {
                    $tdClass .= ' widget_my_projects_first_public';
                }

                $html .= '<tr class="'.util_get_alt_row_color($i++).'" >';

                // Privacy
                if ($display_privacy) {
                    if ($row['access'] === Project::ACCESS_PRIVATE) {
                        $privacy = 'icon-lock fa fa-lock';
                    } else {
                        $privacy = 'icon-unlock fa fa-unlock';
                    }
                    $html .= '<td class="widget_my_projects_privacy'.$tdClass.'"><i class="'.$privacy.' dashboard-widget-my-projects-icons"></i></td>';
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

                // Mailing tool
                $html .= '<td class="'.$tdClass.'">';
                $html .= '<a class="massmail-project-member-link" href="#massmail-project-members" data-project-id="'.$row['group_id'].'" title="'.$GLOBALS['Language']->getText('my_index','send_mail',$row['group_name']).'" data-toggle="modal"><span class="icon-envelope-alt fa fa-envelope-o"></span></a>';
                $html .= '</td>';

                // Remove from project
                $html .= '<td class="widget_my_projects_remove'.$tdClass.'">';
                if ($row['admin_flags'] != 'A') {
                    $html .= html_trash_link_fontawesome('rmproject.php?group_id='.$row['group_id'], $GLOBALS['Language']->getText('my_index', 'quit_proj'));
                } else {
                    $html .= '&nbsp;';
                }
                $html .= '</td>';

                $html .= '</tr>';

                $prevIsPublic = ($row['access'] !== Project::ACCESS_PRIVATE);
            }

            if ($display_privacy) {
                // Legend
                $html .= '<tr>';
                $html .= '<td colspan="5" class="widget_my_projects_legend">';
                $html .= '<span class="widget_my_projects_legend_title dashboard-widget-my-projects-legend-title">'.$GLOBALS['Language']->getText('my_index', 'my_projects_legend').'</span>';
                $html .= '<span class="dashboard-widget-my-projects-legend-content"><i class="icon-lock fa fa-lock dashboard-widget-my-projects-icons"></i> '.$GLOBALS['Language']->getText('project_privacy', 'private').'</span> ';
                $html .= '<span class="dashboard-widget-my-projects-legend-content"><i class="icon-unlock fa fa-unlock dashboard-widget-my-projects-icons"></i> '.$GLOBALS['Language']->getText('project_privacy', 'public').'</span>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';

            $html .= $this->fetchMassMailForm($token, $use_legacy_template);

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
            . "groups.access,"
            . "user_group.admin_flags "
            . "FROM groups,user_group "
            . "WHERE groups.group_id=user_group.group_id "
            . "AND user_group.user_id='". db_ei(user_getid()) ."' "
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
                if ( db_result($result,$i,'access') == Project::ACCESS_PRIVATE ) {
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

    private function fetchMassMailForm(CSRFSynchronizerToken $token, $use_legacy_template) {
        $presenter = new MassmailFormPresenter(
            $token,
            $GLOBALS['Language']->getText('my_index', 'massmail_form_title'),
            'massmail_to_project_members.php'
        );

        $template_factory = TemplateRendererFactory::build();
        $renderer         = $template_factory->getRenderer($presenter->getTemplateDir());

        if ($use_legacy_template) {
            return $renderer->renderToString('contact-modal-for-legacy-dashboard', $presenter);
        }

        return $renderer->renderToString('contact-modal', $presenter);
    }
}
