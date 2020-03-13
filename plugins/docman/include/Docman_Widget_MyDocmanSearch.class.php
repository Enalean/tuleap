<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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
* Docman_Widget_MyDocmanSearch
*/
class Docman_Widget_MyDocmanSearch extends Widget
{

    public $pluginPath;

    public function __construct($pluginPath)
    {
        parent::__construct('plugin_docman_mydocman_search');
        $this->_pluginPath = $pluginPath;
    }

    public function getTitle()
    {
        return dgettext('tuleap-docman', 'Document Id Search');
    }

    public function getContent()
    {
        $html = '';
        $request = HTTPRequest::instance();
        $um = UserManager::instance();
        $user = $um->getCurrentUser();

        $vFunc = new Valid_WhiteList('docman_func', array('show_docman'));
        $vFunc->required();
        if ($request->valid($vFunc)) {
            $func = $request->get('docman_func');
        } else {
            $func = '';
        }
        $vDocmanId = new Valid_UInt('docman_id');
        $vDocmanId->required();
        if ($request->valid($vDocmanId)) {
            $docman_id = $request->get('docman_id');
        } else {
            $docman_id = '';
        }

        $url = '';
        if ($request->get('dashboard_id')) {
            $url = '?dashboard_id=' . urlencode($request->get('dashboard_id'));
        }

        $html .= '<form method="post" action="' . $url . '">';
        $html .= '<input type="hidden" name="docman_func" value="show_docman" />';
        $html .= '<div class="tlp-form-element">
                    <label class="tlp-label" for="docman_id">' .
            dgettext('tuleap-docman', 'Search document id') .
            '</label>
                    <input type="text" name="docman_id" value="' . $docman_id . '" id="docman_id" class="tlp-input" placeholder="123"/>
                  </div>';
        $html .= '<input type="submit" class="tlp-button-primary" value="' . dgettext('tuleap-docman', 'Search') . '"/>';
        $html .= '</form>';

        if (($func == 'show_docman') && $docman_id) {
            $res = $this->returnAllowedGroupId($docman_id, $user);

            if ($res) {
                $dPm = Docman_PermissionsManager::instance($res['group_id']);
                $itemPerm = $dPm->userCanAccess($user, $docman_id);

                if ($itemPerm) {
                    $html .= '<p><a href="/plugins/docman/?group_id=' . $res['group_id'] . '&action=details&id=' . $docman_id . '&section=properties">Show &quot;' . $res['title'] . '&quot; Properties</a></p>';
                    return $html;
                }
            }
            $html .= '<p>' . dgettext('tuleap-docman', 'You do not have the permission to access the document') . '</p>';
        }

        return $html;
    }

    /**
     * Check if given document is in a project readable by user.
     *
     * Returns project info if:
     * * the document belongs to a public project
     * ** And the user is active (not restricted)
     * ** Or user is restricted but member of the project.
     * * or a private one and the user is a member of it
     * else 0
     *
     * @param $docman_id int  Document Id
     * @param $user      User User Id
     * @return array|0
     * @psalm-return array{group_id: int, title:string}|0
     **/
    public function returnAllowedGroupId($docman_id, $user)
    {
        $sql_group = 'SELECT group_id,title FROM  plugin_docman_item WHERE' .
                         ' item_id = ' . db_ei($docman_id);

        $res_group = db_query($sql_group);

        if ($res_group && db_numrows($res_group) == 1) {
            $row = db_fetch_array($res_group);
            $res = [
                'group_id' => (int) $row['group_id'],
                'title'    => (string) $row['title']
            ];

            $project = ProjectManager::instance()->getProject($res['group_id']);
            if ($project->isPublic()) {
                // Check restricted user
                if (($user->isRestricted() && $user->isMember($res['group_id'])) || !$user->isRestricted()) {
                    return $res;
                }
            } else {
                if ($user->isMember($res['group_id'])) {
                    return $res;
                }
            }
        }
        return 0;
    }

    public function getCategory()
    {
        return dgettext('tuleap-docman', 'Document manager');
    }

    public function getDescription()
    {
        return dgettext('tuleap-docman', 'Redirect to document with given id.');
    }
}
