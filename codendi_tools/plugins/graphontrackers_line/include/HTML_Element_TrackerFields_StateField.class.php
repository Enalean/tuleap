<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 * 
 * Updated by Nicolas Terray, 2008, Xerox Codendi.
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

require_once('common/html/HTML_Element_Selectbox_TrackerFields_Selectboxes.class.php');
require_once('common/html/HTML_Element_Columns.class.php');

/**
 * Define an html selectbox field 
 */
class HTML_Element_TrackerFields_StateField extends HTML_Element_Columns {

    public function __construct($label, $name, $value, $state_source_field, $state_target_field) {
        $onchange = '';
        $onchange .= "var states = {\n";
        $sql = sprintf("SELECT af.field_name,afvl.value_id,afvl.value
                        FROM artifact_field_value_list afvl
                        JOIN artifact_field af
                        USING(field_id,group_artifact_id)
                        WHERE af.group_artifact_id = %d
                        ORDER BY af.field_name",
                        db_ei($GLOBALS['ath']->getID())
                       );
        $res = db_query($sql);
        $old_field = '';
        while($row = db_fetch_array($res)) {
            if ($old_field != $row['field_name']) {
                if ($old_field) {
                    $onchange .= "],\n";
                }
                $onchange .= "'". addslashes($row['field_name']) ."': [";
                $old_field = $row['field_name'];
            } else {
                $onchange .= ",";
            }
            $onchange .= "{";
            $onchange .= "value: '". addslashes($row['value_id']) ."', ";
            $onchange .= "text: '". addslashes($row['value']) ."' ";
            $onchange .= "}";
        }
        if ($old_field) {
            $onchange .= "]";
        }
        $onchange .= "}\n";

        $onchange .= "removeAllOptions(document.getElementById('". $state_source_field->getId() ."'));";
        $onchange .= "for (var i = 0 ; i < states[this.value].length ; i++) {";
        $onchange .= "    addOption(document.getElementById('". $state_source_field->getId() ."'), states[this.value][i]['text'], states[this.value][i]['value'], false);";
        $onchange .= "}";

        $onchange .= "removeAllOptions(document.getElementById('". $state_target_field->getId() ."'));\n";
        $onchange .= "addOption(document.getElementById('". $state_target_field->getId() ."'),'".$GLOBALS['Language']->getText('plugin_graphontrackers_empty_select','none_value')."','',false);";
        $onchange .= "for (var i = 0 ; i < states[this.value].length ; i++) {";
        $onchange .= "    addOption(document.getElementById('". $state_target_field->getId() ."'), states[this.value][i]['text'], states[this.value][i]['value'], false);";
        $onchange .= "}";
        
        $field = new HTML_Element_Selectbox_TrackerFields_Selectboxes($label, $name, $value, true, $onchange, false);
        parent::__construct($field, $state_source_field, $state_target_field);
    }
}

?>
