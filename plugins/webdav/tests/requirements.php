<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * This file is used only to simulate classes needed to be just declared in tests
 */

class Sabre_DAV_Exception extends Exception {}
class Sabre_DAV_Exception_RequestedRangeNotSatisfiable extends Sabre_DAV_Exception {}
class Sabre_DAV_Exception_FileNotFound extends Sabre_DAV_Exception {}
class Sabre_DAV_Exception_Conflict extends Sabre_DAV_Exception {}
class Sabre_DAV_Exception_Forbidden extends Sabre_DAV_Exception {}
class Sabre_DAV_Exception_MethodNotAllowed extends Sabre_DAV_Exception {}
class Sabre_DAV_Exception_BadRequest extends Sabre_DAV_Exception {}
class WebDAVExceptionServerError extends Sabre_DAV_Exception {}

class Sabre_DAV_File {}
class Sabre_DAV_Directory {}

class Sabre_DAV_ObjectTree {}
class Sabre_DAV_URLUtil {
    static function splitPath($path) {
        $matches = array();
        if(preg_match('/^(?:(?:(.*)(?:\/+))?([^\/]+))(?:\/?)$/u',$path,$matches)) {
            return array($matches[1],$matches[2]);
        } else {
            return array(null,null);
        }
    }
}

?>