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

require_once('Docman_Document.class.php');

/**
 * Wiki is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Wiki extends Docman_Document {
    
    function Docman_Wiki($data = null) {
        parent::Docman_Document($data);
    }
    
    var $pagename;
    function getPagename() { 
        return $this->pagename; 
    }
    function setPagename($pagename) { 
        $this->pagename = $pagename;
    }
    
    function initFromRow($row) {
        parent::initFromRow($row);
        $this->setPagename($row['wiki_page']);
    }
    function toRow() {
        $row = parent::toRow();
        $row['wiki_page'] = $this->getPagename();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_WIKI;
        return $row;
    }

    function accept(&$visitor, $params = array()) {
        return $visitor->visitWiki($this, $params);
    }
}

?>