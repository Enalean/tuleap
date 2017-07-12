<?php
/**
 * Copyright Enalean (c) 2011-2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Project_Admin_UGroup_View_Permissions extends Project_Admin_UGroup_View {

    const IDENTIFIER = 'permissions';

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;

    public function __construct(ProjectUGroup $ugroup) {
        parent::__construct($ugroup);

        $this->permissions_manager = PermissionsManager::instance();
        $this->event_manager       = EventManager::instance();
        $this->html_purifier       = Codendi_HTMLPurifier::instance();
        $this->release_factory     = new FRSReleaseFactory();
    }

    public function getContent() {
        $content = '<h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','permissions_title') .'</h2>';
        $data    = $this->getFormattedData();

        if ($data) {
            $content .= '<p>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'ug_perms').'<p>';

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('project_admin_editugroup', 'permission');
            $title_arr[] = $GLOBALS['Language']->getText('project_admin_editugroup', 'resource_name');
            $content .= '<table class="admin-permissions table">';
            $content .= '<thead><tr>';
            $content .= '<th>'. $GLOBALS['Language']->getText('project_admin_editugroup', 'permission') .'</th>';
            $content .= '<th>'. $GLOBALS['Language']->getText('project_admin_editugroup', 'resource_name') .'</th>';
            $content .= '</tr></thead>';
            $content .= '<tbody>';


            foreach ($data as $group_permission_data) {
                $content .= '<tr>';
                $content .= '<td>';
                $content .= $group_permission_data['permission_name'];
                $content .= '</td>';
                $content .= '<td>';
                $content .= $group_permission_data['content'];
                $content .= '</td>';
                $content .= '</td>';
            }

            $content .= '</tbody></table><p>';
        } else {
            $content .= '<p>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'no_perms').'.</p>';
        }
        return $content;
    }

    /**
     * @return array
     */
    private function getFormattedData()
    {
        $data      = array();
        $dar       = $this->permissions_manager->searchByUgroupId($this->ugroup->getId());
        $row_count = 0;

        if ($dar && !$dar->isError() && $dar->rowCount() >0) {
            foreach ($dar as $row) {
                if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $atid = permission_extract_atid($row['object_id']);
                    if (isset($tracker_field_displayed[$atid])) {
                        continue;
                    }
                    $objname = permission_get_object_name('TRACKER_ACCESS_FULL', $atid);
                } else {
                    $objname = permission_get_object_name($row['permission_type'], $row['object_id']);
                }

                if (!$objname) {
                    continue;
                }

                if ($row['permission_type'] == 'PACKAGE_READ') {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'package')
                        . ' <a href="/file/admin/editpackagepermissions.php?package_id='
                        . urlencode($row['object_id']) . '&group_id=' . urlencode($this->ugroup->getProjectId()) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if ($row['permission_type'] == 'RELEASE_READ') {
                    $release         = $this->release_factory->getFRSReleaseFromDb($row['object_id']);
                    $package_name    = $release->getPackage()->getName();
                    $package_id      = $release->getPackageID();

                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'release')
                        . ' <a href="/file/admin/editreleasepermissions.php?release_id=' . urlencode($row['object_id']) . '&group_id=' . urlencode($this->ugroup->getProjectId()) . '&package_id=' . urlencode($package_id) . '">'
                        . $this->html_purifier->purify($objname) . '</a> ('
                        . $GLOBALS['Language']->getText('project_admin_editugroup', 'from_package')
                        . ' <a href="/file/admin/editreleases.php?package_id=' . urlencode($package_id) . '&group_id=' . urlencode($this->ugroup->getProjectId()) . '">'
                        . $this->html_purifier->purify($package_name) . '</a> )';
                } else if ($row['permission_type'] == 'WIKI_READ') {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'wiki')
                        . ' <a href="/wiki/admin/index.php?view=wikiPerms&group_id=' . urlencode($this->ugroup->getProjectId()) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if ($row['permission_type'] == 'WIKIPAGE_READ') {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'wiki_page')
                        . ' <a href="/wiki/admin/index.php?group_id=' . urlencode($this->ugroup->getProjectId()) . '&view=pagePerms&id=' . urlencode($row['object_id']) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if (strpos($row['permission_type'], 'TRACKER_ACCESS') === 0) {
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'tracker')
                        . ' <a href="/tracker/admin/?func=permissions&perm_type=tracker&group_id=' . urlencode($this->ugroup->getProjectId()) . '&atid=' . urlencode($row['object_id']) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $tracker_field_displayed[$atid] = 1;
                    $atid                           = permission_extract_atid($row['object_id']);
                    $content = $GLOBALS['Language']->getText('project_admin_editugroup', 'tracker_field')
                        . ' <a href="/tracker/admin/?group_id=' . urlencode($this->ugroup->getProjectId()) . '&atid=' . urlencode($atid) . '&func=permissions&perm_type=fields&group_first=1&selected_id=' . urlencode($this->ugroup->getId()) . '">'
                        . $this->html_purifier->purify($objname) . '</a>';
                } else if ($row['permission_type'] == 'TRACKER_ARTIFACT_ACCESS') {
                    $content = $this->html_purifier->purify($objname, CODENDI_PURIFIER_BASIC);
                } else {
                    $results = false;
                    $this->event_manager->processEvent('permissions_for_ugroup', array(
                        'permission_type' => $row['permission_type'],
                        'object_id' => $row['object_id'],
                        'objname' => $objname,
                        'group_id' => $this->ugroup->getProjectId(),
                        'ugroup_id' => $this->ugroup->getId(),
                        'results' => &$results
                    ));
                    if ($results) {
                        $content = $results;
                    } else {
                        $content = $row['object_id'];
                    }
                }

                $data[$row_count]['permission_name'] = $this->html_purifier->purify(permission_get_name($row['permission_type']));
                $data[$row_count]['content']         = $content;
                $row_count++;
            }

            return $data;
        }
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }
}
