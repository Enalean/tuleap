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

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep
* 
* A step during project registration. Each concrete subclass must provide 
* at least display() to display instruction/form to user.
* 
*/
/* abstract */class RegisterProjectStep {
    var $name;
    var $help;
    function RegisterProjectStep($name, $help) {
        $this->name = $name;
        $this->help = $help;
    }
    /**
    * called before leaving this step
    * @return boolean post-requisites are valid
    */
    function onLeave($request, &$data) {
        return true;
    }
    /**
    * called before entering the step
    * @return boolean post-requisites are valid
    */
    function onEnter($request, &$data) {
        return true;
    }
    /**
    * display form/instructions to user
    */
    function display($data) {
    }
    /**
    * @return boolean data are valid for this step
    */
    function validate($data) {
        return true;
    }
}

?>
