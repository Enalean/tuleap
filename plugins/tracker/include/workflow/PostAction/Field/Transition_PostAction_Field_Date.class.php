<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
require_once(dirname(__FILE__) .'/../Transition_PostAction.class.php');

/**
 * Set the date of a field
 */
class Transition_PostAction_Field_Date extends Transition_PostAction {
    
    /**
     * @const Clear the date.
     */
    const CLEAR_DATE = 1;
    
    /**
     * @const Fille the date to the current time
     */
    const FILL_CURRENT_TIME = 2;
    
    /**
     * @var int the type of the value. CLEAR_DATE | FILL_CURRENT_TIME
     */
    protected $value_type;
    
    /**
     * @var int the field id
     */
    protected $field_id;
    
    /**
     * Constructor
     *
     * @param int $field_id   The field id
     * @param int $value_type The type of the value to set
     */
    public function __construct($field_id, $value_type) {
        $this->field_id   = $field_id;
        $this->value_type = $value_type;
    }
    
    /**
     * @return string The shortname of the post action
     */
    public function getShortName() {
        return 'field_date';
    }
    
    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch() {
        $html = '';
        $select = '<select>';
        $select .= '<option value="'. (int)self::CLEAR_DATE .'" '. ($this->value_type === self::CLEAR_DATE ? 'selected="selected"' : '') .'>empty</option>';
        $select .= '<option value="'. (int)self::FILL_CURRENT_TIME .'" '. ($this->value_type === self::FILL_CURRENT_TIME ? 'selected="selected"' : '') .'>the current date</option>';
        $select .= '</select>';
        $html .= 'Change the value of the field '. $this->field_id .' to '. $select;
        return $html;
    }
    
    /**
     * Execute actions before transition happens
     * 
     * @param Array $fields_data Request field data (array[field_id] => data)
     * 
     * @return void
     */
    public function before(array &$fields_data) {
        if ($this->value_type === self::FILL_CURRENT_TIME) {
            $fields_data[$this->field_id] = $_SERVER['REQUEST_TIME'];
        } else {
            //case : CLEAR_DATE
            $fields_data[$this->field_id] = '';
        }
    }
}
?>
