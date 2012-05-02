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

/**
* PasswordStrategy
*/
class PasswordStrategy {
    
    var $validators;
    var $errors;
    
    /**
    * Constructor
    */
    function PasswordStrategy() {
        $this->validators = array();
        $this->errors     = array();
    }
    
    /**
    * validate
    * 
    * validate a password with the help of validators
    *
    * @param  pwd  
    */
    function validate($pwd) {
        $valid = true;
        foreach($this->validators as $key => $nop) {
            if (!$this->validators[$key]->validate($pwd)) {
                $valid = false;
                $this->errors[$key] = $this->validators[$key]->description();
            }
        }
        return $valid;
    }
    
    /**
    * add
    * 
    * @param  v  
    */
    function add(&$v) {
        $this->validators[] =& $v;
    }
    
}
?>
