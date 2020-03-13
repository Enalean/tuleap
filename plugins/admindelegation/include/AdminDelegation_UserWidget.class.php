<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

/**
 * Display links from and to a project on the summary page.
 */
class AdminDelegation_UserWidget extends Widget //phpcs:ignore
{
    protected $_plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin The plugin
     */
    public function __construct(Plugin $plugin)
    {
        parent::__construct('admindelegation');
        $this->_plugin = $plugin;
    }

    /**
     * Widget title
     *
     * @see src/common/Widget/Widget#getTitle()
     * @return String
     */
    public function getTitle()
    {
        return dgettext('tuleap-admindelegation', 'Admin delegation: search project admins');
    }

    /**
     * Widget description
     *
     * @see src/common/Widget/Widget#getDescription()
     *
     * @return String
     */
    public function getDescription()
    {
        return dgettext('tuleap-admindelegation', 'Site admins can delegate view project admins');
    }

    public function getCategory()
    {
        return dgettext('tuleap-admindelegation', 'Admin delegation');
    }

    public function getProjectAdmins($groupId)
    {
        $admins = array();
        $um = UserManager::instance();
        $sql = 'SELECT u.user_id FROM user u JOIN user_group ug USING(user_id) WHERE ug.admin_flags="A" AND u.status IN ("A", "R") AND ug.group_id = ' . db_ei($groupId);
        $res = db_query($sql);
        while ($row = db_fetch_array($res)) {
            $admins[] = $um->getUserById($row['user_id']);
        }
        return $admins;
    }

    protected function _showProjectAdmins()
    {
        $html = '';

        $hp = Codendi_HTMLPurifier::instance();
        $request = HTTPRequest::instance();
        $vFunc = new Valid_WhiteList('plugin_admindelegation_func', array('show_admins'));
        $vFunc->required();
        if ($request->valid($vFunc)) {
            $func = $request->get('plugin_admindelegation_func');
        } else {
            $func = '';
        }

        $vGroup = new Valid_String('plugin_admindelegation_group');
        $vGroup->required();
        if ($request->valid($vGroup)) {
            $pm      = ProjectManager::instance();
            $project = $pm->getProjectFromAutocompleter($request->get('plugin_admindelegation_group'));
            if ($project && $project->isActive()) {
                $groupValue = $project->getPublicName() . ' (' . $project->getUnixName() . ')';
            } else {
                $groupValue = '';
            }
        } else {
            $project    = false;
            $groupValue = '';
        }

        $html .= '<form method="post" action="">';
        $html .= '<div class="tlp-form-element">';
        $html .= '<label class="tlp-label" for="plugin_admindelegation_func">' . dgettext('tuleap-admindelegation', 'Show administrators of project:') . '</label>';
        $html .= '<input type="hidden" name="plugin_admindelegation_func" value="show_admins" />';
        $html .= '<input type="text" class="tlp-input" name="plugin_admindelegation_group" value="' . $hp->purify($groupValue) . '" size ="40" id="plugin_admindelegation_group" />';
        $html .= '</div>';
        $html .= '<input type="submit" class="tlp-button-primary" value="' . dgettext('tuleap-admindelegation', 'Search') . '"/>';
        $html .= '</form>';

        $js = "new ProjectAutoCompleter('plugin_admindelegation_group', '" . util_get_dir_image_theme() . "', false);";
        $GLOBALS['HTML']->includeFooterJavascriptSnippet($js);

        if ($func == 'show_admins' && $project && $project->isActive()) {
            $allAdmins = array();
            $users = $this->getProjectAdmins($project->getId());
            if (count($users) > 0) {
                $uh = UserHelper::instance();
                $html .= '<table width="100%" class="tlp-table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>' . dgettext('tuleap-admindelegation', 'Name') . '</th>';
                $html .= '<th>' . dgettext('tuleap-admindelegation', 'Email') . '</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';
                $i = 1;
                foreach ($users as $u) {
                    $mailto = $u->getEmail();
                    $allAdmins[] = $mailto;
                    $html .= '<tr class="' . util_get_alt_row_color($i++) . '">';
                    $html .= '<td>' . $hp->purify($uh->getDisplayNameFromUser($u)) . '</td>';
                    $html .= '<td><a href="mailto:' . $hp->purify($mailto) . '">' . $hp->purify($u->getEmail()) . '</a></td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';

                // Mail to all admins
                $html .= '<div style="text-align:center" class="' . util_get_alt_row_color($i++) . '">';
                $html .= '<a href="mailto:' . $hp->purify(implode(',', $allAdmins)) . '?Subject=' . $hp->purify(sprintf(dgettext('tuleap-admindelegation', '[%1$s] Project %2$s:'), ForgeConfig::get('sys_name'), $project->getPublicName())) . '">' . dgettext('tuleap-admindelegation', 'Mail to all admins') . '</a>';
                $html .= '</div>';
            }
        }
        return $html;
    }


    /**
     * Widget content
     *
     * @see src/common/Widget/Widget#getContent()
     * @return String
     */
    public function getContent()
    {
        $html = '';
        $usm  = new AdminDelegation_UserServiceManager(
            new AdminDelegation_UserServiceDao(),
            new AdminDelegation_UserServiceLogDao()
        );
        if ($usm->isUserGrantedForService(UserManager::instance()->getCurrentUser(), AdminDelegation_Service::SHOW_PROJECT_ADMINS)) {
            $html .= $this->_showProjectAdmins();
        }

        return $html;
    }
}
