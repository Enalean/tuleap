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

require_once('Docman_Item.class.php');

/**
 * Document is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Document extends Docman_Item {
    
    function Docman_Document($data = null) {
        parent::Docman_Item($data);
    }
    
    function accept(&$visitor, $params = array()) {
        return $visitor->visitDocument($this, $params);
    }
}

?>