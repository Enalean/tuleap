<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
 */

require_once('Docman_ItemFactory.class.php');
require_once('www/project/export/project_export_utils.php');
require_once('www/project/admin/project_admin_utils.php');

class Docman_PermissionsExport {
    protected $group        = null;
    protected $ugroups      = null;
    protected $parentTitles = array();

    public function __construct(Project $group) {
        $this->group = $group;
    }
    
    public function fetchPerms($parentIds, &$output) {
        if(count($parentIds) == 0) {
            return;
        }

        $sql = 'SELECT i.item_id, i.item_type, i.title, i.parent_id, p.permission_type, p.ugroup_id, ug.name'.
        ' FROM plugin_docman_item i'.
        '   JOIN permissions p ON (p.object_id = CAST(i.item_id as CHAR) AND p.permission_type IN (\'PLUGIN_DOCMAN_READ\', \'PLUGIN_DOCMAN_WRITE\', \'PLUGIN_DOCMAN_MANAGE\'))'.
        '   JOIN ugroup ug ON (ug.ugroup_id = p.ugroup_id)'.
        ' WHERE i.group_id = '.$this->group->getId().
        '   AND i.parent_id IN ('.implode(',',$parentIds).') '.
        '   AND i.delete_date IS NULL'.
        ' ORDER BY i.rank ASC, permission_type';

        $result = db_query($sql);
        $title='';
        $newParentIds = array();
        while(($row = db_fetch_array($result))) {
            $this->parentTitles[$row['item_id']] = $this->parentTitles[$row['parent_id']].'/'.$row['title'];
            $output[$row['item_id']]['title'] = $this->parentTitles[$row['item_id']];
            $output[$row['item_id']]['type']  = $row['item_type'];
            foreach($this->getUgroups() as $id => $name) {
                if($row['ugroup_id'] == $id) {
                    $output[$row['item_id']][$id] = $row['permission_type'];
                }
            }
            if ($row['item_type'] == '1') {
                $newParentIds[] = $row['item_id'];
            }
        }
        $this->fetchPerms($newParentIds, $output);
    }

    public function getUgroups() {
        if ($this->ugroups === null) {
            $result = ugroup_db_get_existing_ugroups($this->group->getId(), array($GLOBALS['UGROUP_ANONYMOUS'], $GLOBALS['UGROUP_REGISTERED'], $GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
            while(($row = db_fetch_array($result))) {
                $this->ugroups[$row['ugroup_id']] = util_translate_name_ugroup($row['name']);
            }
        }
        return $this->ugroups;
    }

    public function gatherPermissions() {
        // Collect data
        $itemFactory    = new Docman_ItemFactory($this->group->getId());
        $rootItem       = $itemFactory->getRoot($this->group->getId());
        $this->parentTitles[$rootItem->getId()] = '';
        $output         = array();
        $this->fetchPerms(array($rootItem->getId()), $output);
        return $output;
    }

    public function toCSV() {
        $output   = $this->gatherPermissions();
        $sep      = get_csv_separator();
        $date     = util_timestamp_to_userdateformat($_SERVER['REQUEST_TIME'], true);
        $filename = 'export_permissions_'.$this->group->getUnixName().'_'.$date.'.csv';
        header('Content-Disposition: filename='.$filename);
        header('Content-Type: text/csv');
        // Context
        echo $GLOBALS['Language']->getText('plugin_docman','format_export_project').$sep.tocsv($this->group->getPublicName()).$sep.tocsv($this->group->getUnixName()).$sep.$this->group->getId().PHP_EOL;
        echo $GLOBALS['Language']->getText('plugin_docman','format_export_date').$sep.format_date(util_get_user_preferences_export_datefmt(), $_SERVER['REQUEST_TIME']).PHP_EOL;
        echo PHP_EOL;
        
        // Datas
        echo $GLOBALS['Language']->getText('plugin_docman','format_id').$sep;
        echo $GLOBALS['Language']->getText('plugin_docman','format_path').$sep;
        echo $GLOBALS['Language']->getText('plugin_docman','format_type').$sep;
        foreach($this->getUgroups() as $id => $name) {
            echo $name.$sep;
        }
        echo PHP_EOL;
        foreach($output as $itemid => $row) {
            echo $itemid.$sep;
            echo tocsv($row['title']).$sep;
            echo $this->itemTypeToString($row['type']).$sep;
            foreach($this->getUgroups() as $id => $name) {
                if (isset($row[$id])) {
                    $this->itemPermToString($row[$id]);
                }
                echo $sep;
            }
            echo PHP_EOL;
        }
    }

    public function itemTypeToString($type) {
        switch($type) {
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

    public function itemPermToString($perm) {
        switch($perm) {
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

    public function renderDefinitionFormat() {
        project_admin_header(array('title'=>$GLOBALS['Language']->getText('plugin_docman','export_format')));
        
        echo '<h3>'.$GLOBALS['Language']->getText('plugin_docman','perm_exp_format').'</h3>';
        echo '<p>'.$GLOBALS['Language']->getText('plugin_docman','perm_exp_format_msg').'</p>';
        $title_arr = array(
            $GLOBALS['Language']->getText('plugin_docman','format_label'),
            $GLOBALS['Language']->getText('plugin_docman','format_sample'),
            $GLOBALS['Language']->getText('plugin_docman','format_description')
        );
        echo  html_build_list_table_top ($title_arr);
        $i = 0;

        echo "<tr class='". util_get_alt_row_color($i++) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_docman','format_id')."</b></td>";
        echo "<td>53</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_docman','format_id_desc')."</td>";
        echo "</tr>";

        echo "<tr class='". util_get_alt_row_color($i++) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_docman','format_path')."</b></td>";
        echo "<td>/My Folder/My Document</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_docman','format_path_desc')."</td>";
        echo "</tr>";
        
        echo "<tr class='". util_get_alt_row_color($i++) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_docman','format_type')."</b></td>";
        echo "<td>File</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_docman','format_type_desc')."</td>";
        echo "</tr>";

        echo "<tr class='". util_get_alt_row_color($i++) ."'>";
        echo "<td><b>".$GLOBALS['Language']->getText('plugin_docman','format_user_group')."</b></td>";
        echo "<td>Developper Group</td>";
        echo "<td>".$GLOBALS['Language']->getText('plugin_docman','format_user_group_desc')."</td>";
        echo "</tr>";

        echo "</table>";
        site_project_footer(array());
    }
}


?>