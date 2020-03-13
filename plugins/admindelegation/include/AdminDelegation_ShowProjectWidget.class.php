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
class AdminDelegation_ShowProjectWidget extends Widget
{
    protected $_plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin The plugin
     */
    public function __construct(Plugin $plugin)
    {
        parent::__construct('admindelegation_projects');
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
        return dgettext('tuleap-admindelegation', 'Admin delegation: search all projects');
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
        return dgettext('tuleap-admindelegation', 'Site admins can delegate view all projects to you');
    }

    public function getCategory()
    {
        return dgettext('tuleap-admindelegation', 'Admin delegation');
    }

    public function getAllProject($offset, $limit, $condition, $pattern)
    {
        if (count($condition) > 0) {
            $statements   = '(';
            $i            = 0;
            $nbConditions = count($condition) - 1;
            for ($i; $i < $nbConditions; $i++) {
                $statements .= db_es($condition[$i]) . ' LIKE "%' . db_es($pattern) . '%" OR ';
            }
            $statements .= db_es($condition[$i]) . ' LIKE "%' . db_es($pattern) . '%") AND ';
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS group_name, group_id, unix_group_name, access FROM groups WHERE ' . $statements . ' status = "A" ORDER BY register_time DESC LIMIT ' . db_ei($offset) . ', ' . db_ei($limit);
        $res = db_query($sql);

        $sql = 'SELECT FOUND_ROWS() as nb';
        $res_numrows = db_query($sql);
        $row = db_fetch_array($res_numrows);

        return array('projects' => $res, 'numrows' => $row['nb']);
    }

    protected function _showAllProject()
    {
        $request = HTTPRequest::instance();

        $urlParam = '';

        $vFunc = new Valid_WhiteList('plugin_admindelegation_func', array('show_projects'));
        $vFunc->required();
        if ($request->valid($vFunc)) {
            $func = $request->get('plugin_admindelegation_func');
        } else {
            $func = '';
        }

        $condition    = array();
        $allCriterias = array('group_name', 'unix_group_name', 'short_description');
        foreach ($allCriterias as $val) {
            $selectedCriteria[$val] = '';
        }
        $vCriteria    = new Valid_WhiteList('criteria', $allCriterias);
        $vCriteria->required();
        if ($request->validArray($vCriteria)) {
            $criteria = $request->get('criteria');
        } else {
            $criteria = $allCriterias;
        }
        foreach ($criteria as $val) {
            $condition[] = $val;
            $urlParam   .= '&criteria[]=' . urlencode($val);
            $selectedCriteria[$val] = 'checked="checked"';
        }

        $vPattern = new Valid_String('plugin_admindelegation_pattern');
        $vPattern->required();
        if ($request->valid($vPattern)) {
            $pattern = $request->get('plugin_admindelegation_pattern');
        } else {
            $pattern = '';
        }

        $offset = $request->getValidated('offset', 'uint', 0);
        if (!$offset || $offset < 0) {
            $offset = 0;
        }
        $limit  = 10;

        $purifier = Codendi_HTMLPurifier::instance();

        $html = '';
        $html .= '<form method="post" action="">';

        $html .= '<div class="tlp-form-element">';
        $html .= '<label class="tlp-label" for="plugin_admindelegation_pattern">' . dgettext('tuleap-admindelegation', 'Show all projects containing:') . '</label>';
        $html .= '<input type="hidden" name="plugin_admindelegation_func" value="show_projects" />';
        $html .= '<input type="text" name="plugin_admindelegation_pattern" class="tlp-input" placeholder="' . dgettext('tuleap-admindelegation', 'Search') . '" value="' . $purifier->purify($pattern) . '" size ="40" id="plugin_admindelegation_pattern" />';
        $html .= '</div>';

        $html .= '<div class="tlp-form-element">';
        $html .= '<label for="plugin_admindelegation_group_name" class="tlp-label tlp-checkbox">' .
            '<input type="checkbox" name="criteria[]" value="group_name" id="plugin_admindelegation_group_name" ' . $purifier->purify($selectedCriteria['group_name']) . ' />' .
            dgettext('tuleap-admindelegation', 'Group Name') .
            '</label>';

        $html .= '<label for="plugin_admindelegation_unix_group_name" class="tlp-label tlp-checkbox">' .
            '<input type="checkbox" name="criteria[]" value="unix_group_name" id="plugin_admindelegation_unix_group_name" ' . $purifier->purify($selectedCriteria['unix_group_name']) . ' />' .
            dgettext('tuleap-admindelegation', 'Short Name') .
            '</label>';

        $html .= '<label for="plugin_admindelegation_short_description" class="tlp-label tlp-checkbox">' .
            '<input type="checkbox" name="criteria[]" value="short_description" id="plugin_admindelegation_short_description" ' . $purifier->purify($selectedCriteria['short_description']) . ' />' .
            dgettext('tuleap-admindelegation', 'Description') .
            '</label>';
        $html .= '</div>';
        $html .= '<input type="submit" class="tlp-button-primary" value="' . dgettext('tuleap-admindelegation', 'Search') . '"/>';

        $html .= '</form>';

        if ($func == 'show_projects') {
            $res = $this->getAllProject($offset, $limit, $condition, $pattern);

            if ($res['numrows'] > 0) {
                $html .= '<table width="100%" class="tlp-table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>' . dgettext('tuleap-admindelegation', 'Project name') . '</th>';
                $html .= '<th>' . dgettext('tuleap-admindelegation', 'Project id') . '</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';
                $i = 1;
                while ($row = db_fetch_array($res['projects'])) {
                    $html .= '<tr class="' . util_get_alt_row_color($i++) . '">';
                    $html .= '<td>';
                    $html .= '<a href="/projects/' . $purifier->purify(urlencode($row['unix_group_name'])) . '">' . $purifier->purify($row['group_name']) . '</a>';
                    if ($row['access'] === Project::ACCESS_PRIVATE || $row['access'] === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
                        $html .= '&nbsp;(*)';
                    }
                    $html .= '</td>';
                    $html .= '<td>' . $purifier->purify($row['group_id']) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';

                $html .= '</table>';

                $html .= '<div style="text-align:center" class="' . util_get_alt_row_color($i++) . '">';
                if ($offset > 0) {
                    $href  = '?plugin_admindelegation_func=show_projects&offset=' . urlencode($offset - $limit) . $urlParam . '&plugin_admindelegation_pattern=' . urlencode($pattern) . '&dashboard_id=' . urlencode($this->getDashboardId());
                    $html .= '<a href="' . $purifier->purify($href) . '">[ ' . dgettext('tuleap-admindelegation', 'Previous') . ' ]</a>';
                    $html .= '&nbsp;';
                }
                if (($offset + $limit) < $res['numrows']) {
                    $href  = '?plugin_admindelegation_func=show_projects&offset=' . urlencode($offset + $limit) . $urlParam . '&plugin_admindelegation_pattern=' . urlencode($pattern) . '&dashboard_id=' . urlencode($this->getDashboardId());
                    $html .= '&nbsp;';
                    $html .= '<a href="' . $purifier->purify($href) . '">[ ' . dgettext('tuleap-admindelegation', 'Next') . ' ]</a>';
                }
                $html .= '</div>';
                $html .= '<div style="text-align:left" class="' . util_get_alt_row_color($i++) . '">';
                $html .= '(*)&nbsp;' . $GLOBALS['Language']->getText('my_index', 'priv_proj');
                $html .= '&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;' . $purifier->purify(sprintf(dgettext('tuleap-admindelegation', '%1$s project(s) found'), $res['numrows']));
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

        if ($usm->isUserGrantedForService(UserManager::instance()->getCurrentUser(), AdminDelegation_Service::SHOW_PROJECTS)) {
            $html .= $this->_showAllProject();
        }
        return $html;
    }
}
