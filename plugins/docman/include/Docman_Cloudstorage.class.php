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
 * URL is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Cloudstorage extends Docman_Document {
    
    function Docman_Cloudstorage($data = null) {
        parent::Docman_Document($data);
    }

    var $documentId;
    var $serviceName;
    
    function getDocumentId() { 
        return $this->documentId; 
    }
    function setDocumentId($url) { 
        $this->documentId = $url;
    }
    
    function getServiceName() { 
        return $this->serviceName; 
    }
    function setServiceName($name) { 
        $this->serviceName = $name;
    }    
    
    function initFromRow($row) {
        parent::initFromRow($row);
        $this->setDocumentId($row['cs_docid']);
        $this->setServiceName($row['cs_service']);
    }
    function toRow() {
        $row = parent::toRow();
        $row['cs_docid'] = $this->getDocumentId(); // url or id, same spirt...
        $row['cs_service'] = $this->getServiceName();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_CLOUDSTORAGE;
        return $row;
    }

    function accept(&$visitor, $params = array()) {
        return $visitor->visitCloudstorage($this, $params);
    }
}

?>
