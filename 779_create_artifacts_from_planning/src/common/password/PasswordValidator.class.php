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
* PasswordValidator
*/
/* abstract */ class PasswordValidator {
    var $description;
    /**
    * PasswordValidator
    * 
    * @param  description  
    */
    function PasswordValidator($description) {
        $this->description = $description;
    }
    
    /**
    * validate
    * 
    * @return boolean true if the password is valid
    *
    */
    /* abstract */ function validate($pwd) {
        return false;
    }
    
    /**
    * description
    * 
    * @return string descrption of the validator
    *
    */
    function description() {
        return $this->description;
    }
    
}
?>
