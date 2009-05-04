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

define('SERVICE_MASTER',    'master');
define('SERVICE_SAME',      'same');
define('SERVICE_SATELLITE', 'satellite');
require_once('common/server/ServerFactory.class.php');
/**
* Service
*/
class Service {
    
    var $data;
	
    /**
    * Constructor
    */
    function Service($data) {
        $this->data = $data;
    }
    
    function getGroupId() {
        return $this->data['group_id'];
    }
    function getId() {
        return $this->data['service_id'];
    }
    function getDescription() {
        return $this->data['description'];
    }
    function getShortName() {
        return $this->data['short_name'];
    }
    function getLabel() {
        return $this->data['label'];
    }
    function getRank() {
        return $this->data['rank'];
    }
    function isUsed() {
        return $this->data['is_used'];
    }
    function isActive() {
        return $this->data['is_active'];
    }
    function getServerId() {
        return $this->data['server_id'];
    }
    function getLocation() {
        return $this->data['location'];
    }
    function getUrl($url = null) {
        if (is_null($url)) {
            $url = $this->data['link'];
        }
        if (!$this->isAbsolute($url) && $this->getLocation() != SERVICE_SAME) {
            $sf =& $this->_getServerFactory();
            if ($s =& $sf->getServerById($this->getServerId())) {
                $url = $s->getUrl($this->_sessionIsSecure()) . $url;
            }
        }
        return $url;
    }
    function &_getServerFactory() {
        return new ServerFactory();
    }
    
    /**
    * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
    */
    function isAbsolute($url) {
        $components = array();
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i', $url, $components);
        return isset($components[1]) && $components[1] ? true : false;
    }
    function _sessionIsSecure() {
        return session_issecure();
    }
    function getPublicArea() {
    }
    function isRequestedPageDistributed(&$request) {
        return false;
    }
}

?>
