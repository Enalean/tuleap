<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 * 
 * Updated by Nicolas Terray, 2008, Xerox Codendi Team
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

require_once('common/html/HTML_Element_Selectbox.class.php');

/**
 * Define an html selectbox field for the state of a field
 */
class HTML_Element_TrackerFields_State extends HTML_Element_Selectbox {

    public function __construct($label, $name, $value, $field_name, $with_none = false) {
        parent::__construct($label, $name, $value, $with_none);
        $sql = sprintf('SELECT af.field_name,afvl.value_id,afvl.value
                        FROM artifact_field_value_list afvl
                        JOIN artifact_field af
                        USING(field_id,group_artifact_id)
                        WHERE af.group_artifact_id = %d
                        AND af.field_name = "%s"
                        ORDER BY afvl.order_id',
                        db_ei($GLOBALS['ath']->getID()),
                        db_es($field_name)
                       );
        $res = db_query($sql);
        while($row = db_fetch_array($res)) {
            $selected = $this->value == $row['value_id'];
            $this->addOption(new HTML_Element_Option($row['value'], $row['value_id'], $selected));
        }
    }
}

?>
