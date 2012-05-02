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
* Server
* 
* A server is a physical box that serves Codendi.
*/
class Server {
    
    var $id;
	var $name;
    var $description;
    var $http;
    var $https;
    var $is_master;
    /**
    * Constructor
    */
    function Server($data = array()) {
        $this->id          = isset($data['id'])          ? $data['id']          : null;
        $this->name        = isset($data['name'])        ? $data['name']        : '';
        $this->description = isset($data['description']) ? $data['description'] : '';
        $this->http        = isset($data['http'])        ? $data['http']        : '';
        $this->https       = isset($data['https'])       ? $data['https']       : '';
        $this->is_master   = isset($data['is_master'])   ? $data['is_master']   : 0;
    }
    function getId() {
        return $this->id;
    }
    function getName() {
        return $this->name;
    }
    function getDescription() {
        return $this->description;
    }
    function getHttp() {
        return $this->http;
    }
    function getHttps() {
        return $this->https;
    }
    function isMaster() {
        return $this->is_master;
    }
    /**
    * @param secure boolean
    */
    function getUrl($secure) {
        return $this->https && ($secure || !$this->http) ? $this->https : $this->http;
    }
}
?>
