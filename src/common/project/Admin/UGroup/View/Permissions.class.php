<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

    public function __construct(UGroup $ugroup) {
        parent::__construct($ugroup);
        $this->permissions_manager = PermissionsManager::instance();
        $this->event_manager = EventManager::instance();
        $this->html_purifier = Codendi_HTMLPurifier::instance();
    }

    public function getContent() {
        $content = '<h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','permissions_title') .'</h2>';
        $this->permissions_manager = PermissionsManager::instance();
        $dar = $this->permissions_manager->searchByUgroupId($this->ugroup->getId());
        if ($dar && !$dar->isError() && $dar->rowCount() >0) {
            $content .= '<p>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'ug_perms').'<p>';

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('project_admin_editugroup', 'permission');
            $title_arr[] = $GLOBALS['Language']->getText('project_admin_editugroup', 'resource_name');
            $content .= '<table class="admin_permissions table table-bordered table-striped">';
            $content .= '<thead><tr>';
            $content .= '<th>'. $GLOBALS['Language']->getText('project_admin_editugroup', 'permission') .'</th>';
            $content .= '<th>'. $GLOBALS['Language']->getText('project_admin_editugroup', 'resource_name') .'</th>';
            $content .= '</tr></thead>';
            $content .= '<tbody>';
            $row_num = 0;

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
                $content .= '<TR>';
                $content .= '<TD>'.permission_get_name($row['permission_type']).'</TD>';
                if ($row['permission_type'] == 'PACKAGE_READ') {
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'package')
                        .' <a href="/file/admin/editpackagepermissions.php?package_id='
                        .$row['object_id'].'&group_id='.$this->ugroup->getProjectId().'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'RELEASE_READ') {
                    $package_id=file_get_package_id_from_release_id($row['object_id']);
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'release')
                        .' <a href="/file/admin/editreleasepermissions.php?release_id='.$row['object_id'].'&group_id='.$this->ugroup->getProjectId().'&package_id='.$package_id.'">'
                        .file_get_release_name_from_id($row['object_id']).'</a> ('
                        .$GLOBALS['Language']->getText('project_admin_editugroup', 'from_package')
                        .' <a href="/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$this->ugroup->getProjectId().'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'DOCUMENT_READ') {
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'document')
                        .' <a href="/docman/admin/editdocpermissions.php?docid='.$row['object_id'].'&group_id='.$this->ugroup->getProjectId().'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'DOCGROUP_READ') {
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'document_group')
                        .' <a href="/docman/admin/editdocgrouppermissions.php?doc_group='.$row['object_id'].'&group_id='.$this->ugroup->getProjectId().'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'WIKI_READ') {
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'wiki')
                        .' <a href="/wiki/admin/index.php?view=wikiPerms&group_id='.$this->ugroup->getProjectId().'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'WIKIPAGE_READ') {
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'wiki_page')
                        .' <a href="/wiki/admin/index.php?group_id='.$this->ugroup->getProjectId().'&view=pagePerms&id='.$row['object_id'].'">'
                        .$objname.'</a></TD>';
                } else if (strpos($row['permission_type'], 'TRACKER_ACCESS') === 0) {
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'tracker')
                        .' <a href="/tracker/admin/?func=permissions&perm_type=tracker&group_id='.$this->ugroup->getProjectId().'&atid='.$row['object_id'].'">'
                        .$objname.'</a></TD>';
                } else if (strpos($row['permission_type'], 'TRACKER_FIELD') === 0) {
                    $tracker_field_displayed[$atid]=1;
                    $atid =permission_extract_atid($row['object_id']);
                    $content .= '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'tracker_field')
                        .' <a href="/tracker/admin/?group_id='.$this->ugroup->getProjectId().'&atid='.$atid.'&func=permissions&perm_type=fields&group_first=1&selected_id='.$this->ugroup->getId().'">'
                        .$objname.'</a></TD>';
                } else if ($row['permission_type'] == 'TRACKER_ARTIFACT_ACCESS') {
                    $content .= '<td>'. $this->html_purifier->purify($objname, CODENDI_PURIFIER_BASIC) .'</td>';
                } else {
                    $results = false;
                    $this->event_manager->processEvent('permissions_for_ugroup', array(
                        'permission_type' => $row['permission_type'],
                        'object_id'       => $row['object_id'],
                        'objname'         => $objname,
                        'group_id'        => $this->ugroup->getProjectId(),
                        'ugroup_id'       => $this->ugroup->getId(),
                        'results'         => &$results
                    ));
                    if ($results) {
                        $content .= '<TD>'.$results.'</TD>';
                    } else {
                        $content .= '<TD>'.$row['object_id'].'</TD>';
                    }
                }

                $content .= '</TR>';
                $row_num++;
            }
            $content .= '</tbody></table><p>';
        } else {
            $content .= '<p>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'no_perms').'.</p>';
        }
        return $content;
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }
}

?>
