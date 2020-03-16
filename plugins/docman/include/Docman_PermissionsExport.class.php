<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';
require_once __DIR__ . '/../../../src/www/project/admin/project_admin_utils.php';

class Docman_PermissionsExport
{
    protected $group;
    protected $ugroups;
    protected $parentTitles = array();

    public function __construct(Project $group)
    {
        $this->group = $group;
    }

    public function fetchPerms($parentIds, &$output)
    {
        if (count($parentIds) == 0) {
            return;
        }

        $sql = 'SELECT i.item_id, i.item_type, i.title, i.parent_id, p.permission_type, p.ugroup_id, ug.name' .
        ' FROM plugin_docman_item i' .
        '   JOIN permissions p ON (p.object_id = CAST(i.item_id as CHAR CHARACTER SET utf8) AND p.permission_type IN (\'PLUGIN_DOCMAN_READ\', \'PLUGIN_DOCMAN_WRITE\', \'PLUGIN_DOCMAN_MANAGE\'))' .
        '   JOIN ugroup ug ON (ug.ugroup_id = p.ugroup_id)' .
        ' WHERE i.group_id = ' . $this->group->getId() .
        '   AND i.parent_id IN (' . implode(',', $parentIds) . ') ' .
        '   AND i.delete_date IS NULL' .
        ' ORDER BY i.rank ASC, permission_type';

        $result = db_query($sql);
        $title = '';
        $newParentIds = array();
        while (($row = db_fetch_array($result))) {
            $this->parentTitles[$row['item_id']] = $this->parentTitles[$row['parent_id']] . '/' . $row['title'];
            $output[$row['item_id']]['title'] = $this->parentTitles[$row['item_id']];
            $output[$row['item_id']]['type']  = $row['item_type'];
            foreach ($this->getUgroups() as $id => $name) {
                if ($row['ugroup_id'] == $id) {
                    $output[$row['item_id']][$id] = $row['permission_type'];
                }
            }
            if ($row['item_type'] == '1') {
                $newParentIds[] = $row['item_id'];
            }
        }
        $this->fetchPerms($newParentIds, $output);
    }

    public function getUgroups()
    {
        if ($this->ugroups === null) {
            /** @psalm-suppress DeprecatedFunction */
            $result = ugroup_db_get_existing_ugroups($this->group->getId(), array($GLOBALS['UGROUP_ANONYMOUS'], $GLOBALS['UGROUP_REGISTERED'], $GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
            while (($row = db_fetch_array($result))) {
                $this->ugroups[$row['ugroup_id']] = util_translate_name_ugroup($row['name']);
            }
        }
        return $this->ugroups;
    }

    public function gatherPermissions()
    {
        // Collect data
        $itemFactory    = new Docman_ItemFactory($this->group->getId());
        $rootItem       = $itemFactory->getRoot($this->group->getId());
        $output         = array();
        if ($rootItem !== null) {
            $this->parentTitles[$rootItem->getId()] = '';
            $this->fetchPerms(array($rootItem->getId()), $output);
        }
        return $output;
    }

    public function toCSV()
    {
        $output   = $this->gatherPermissions();
        $sep      = get_csv_separator();
        $date     = DateHelper::formatForLanguage($GLOBALS['Language'], $_SERVER['REQUEST_TIME'], true);
        $filename = 'export_permissions_' . $this->group->getUnixName() . '_' . $date . '.csv';
        header('Content-Disposition: filename=' . $filename);
        header('Content-Type: text/csv');
        // Context
        echo dgettext('tuleap-docman', 'Project') . $sep . tocsv($this->group->getPublicName(), $sep) . $sep . tocsv($this->group->getUnixName(), $sep) . $sep . $this->group->getId() . PHP_EOL;
        echo dgettext('tuleap-docman', 'Export date') . $sep . format_date(util_get_user_preferences_export_datefmt(), $_SERVER['REQUEST_TIME']) . PHP_EOL;
        echo PHP_EOL;

        // Datas
        echo dgettext('tuleap-docman', 'Id') . $sep;
        echo dgettext('tuleap-docman', 'Path') . $sep;
        echo dgettext('tuleap-docman', 'Type') . $sep;
        foreach ($this->getUgroups() as $id => $name) {
            echo $name . $sep;
        }
        echo PHP_EOL;
        foreach ($output as $itemid => $row) {
            echo $itemid . $sep;
            echo tocsv($row['title'], $sep) . $sep;
            echo $this->itemTypeToString($row['type']) . $sep;
            foreach ($this->getUgroups() as $id => $name) {
                if (isset($row[$id])) {
                    $this->itemPermToString($row[$id]);
                }
                echo $sep;
            }
            echo PHP_EOL;
        }
    }

    public function itemTypeToString($type)
    {
        switch ($type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_FOLDER:
                $str = 'Folder';
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $str = 'File';
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $str = 'Link';
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                $str = 'Embedded file';
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                $str = 'Wiki';
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_EMPTY:
                $str = 'Empty';
                break;
            default:
                $str = '';
        }
        return $str;
    }

    public function itemPermToString($perm)
    {
        switch ($perm) {
            case 'PLUGIN_DOCMAN_READ':
                echo 'READ';
                break;
            case 'PLUGIN_DOCMAN_WRITE':
                echo 'WRITE';
                break;
            case 'PLUGIN_DOCMAN_MANAGE':
                echo 'MANAGE';
                break;
        }
    }

    public function renderDefinitionFormat()
    {
        project_admin_header(
            array('title' => dgettext('tuleap-docman', 'Project data export')),
            'data'
        );

        echo '<h3>' . dgettext('tuleap-docman', 'Docman permission export format') . '</h3>';
        echo '<p>' . dgettext('tuleap-docman', 'Docman permission export format') . '</p>';
        $title_arr = array(
            dgettext('tuleap-docman', 'Label'),
            dgettext('tuleap-docman', 'Sample value'),
            dgettext('tuleap-docman', 'Description')
        );
        echo  html_build_list_table_top($title_arr);
        $i = 0;

        echo "<tr class='" . util_get_alt_row_color($i++) . "'>";
        echo "<td><b>" . dgettext('tuleap-docman', 'Id') . "</b></td>";
        echo "<td>53</td>";
        echo "<td>" . dgettext('tuleap-docman', 'Document id') . "</td>";
        echo "</tr>";

        echo "<tr class='" . util_get_alt_row_color($i++) . "'>";
        echo "<td><b>" . dgettext('tuleap-docman', 'Path') . "</b></td>";
        echo "<td>/My Folder/My Document</td>";
        echo "<td>" . dgettext('tuleap-docman', 'The Item path in docman tree') . "</td>";
        echo "</tr>";

        echo "<tr class='" . util_get_alt_row_color($i++) . "'>";
        echo "<td><b>" . dgettext('tuleap-docman', 'Type') . "</b></td>";
        echo "<td>File</td>";
        echo "<td>" . dgettext('tuleap-docman', 'Kind of item (file, link, wiki,...)') . "</td>";
        echo "</tr>";

        echo "<tr class='" . util_get_alt_row_color($i++) . "'>";
        echo "<td><b>" . dgettext('tuleap-docman', 'User Groups...') . "</b></td>";
        echo "<td>Developper Group</td>";
        echo "<td>" . dgettext('tuleap-docman', 'One user group per column. Each one can have either READ, WRITE or MANAGE permission. An empty row means no access.') . "</td>";
        echo "</tr>";

        echo "</table>";
        site_project_footer(array());
    }
}
