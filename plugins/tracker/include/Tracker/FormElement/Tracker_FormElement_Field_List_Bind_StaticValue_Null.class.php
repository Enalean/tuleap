<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Tracker_FormElement_Field_List_Bind_StaticValue.class.php');

class Tracker_FormElement_Field_List_Bind_StaticValue_Null extends Tracker_FormElement_Field_List_Bind_StaticValue {
    const VALUE = 100;

    /**
     * 
     * @param int $id
     * @param string $label
     * @param string $description
     * @param int $rank
     * @param bool $is_hidden
     */
    public function __construct($id = 100, $label = null, $description = '', $rank = 0, $is_hidden = false) {
        if($label === null){
            $label = $GLOBALS['Language']->getText('global','none');
        }
        
        $this->label       = $label;
        $this->description = $description;
        $this->rank        = $rank;
        
        parent::__construct($id, $label, $description, $rank, $is_hidden);
    }
}
?>
