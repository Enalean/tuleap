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

require_once('common/include/URLVerification.class.php');

/**
 * Check the URL validity for WebDAV plugin
 */
class Webdav_URLVerification extends URLVerification {

    protected $webdavHost;

    /**
     * Constructor of the class
     *
     * @param String $host
     *
     * @return void
     */
    function __construct($host) {
        parent::__construct();
        $this->webdavHost = $host;
    }

    /**
     * Returns the WebDAV host
     *
     * @return String
     */
    function getWebDAVHost() {
        return $this->webdavHost;
    }

    /**
     * Checks if the URL is valid or not and throw an error if needed.
     *
     * @param Array $server
     *
     * @return void
     *
     * @see URLVerification#assertValidUrl($server)
     */
    public function assertValidUrl($server) {
        if (strcmp($server['HTTP_HOST'], $this->getWebDAVHost()) == 0) {
            if (!$this->isUsingSSL($server) && $GLOBALS['sys_force_ssl'] == 1) {
                $this->forbiddenError();
            }
        } else {
            //$this->parentAssertValidURL($server);
            parent::assertValidUrl($server);
            
        }
    }

    /**
     * Call to URLVerification->assertValidUrl
     *
     * @param Array $server
     *
     * @return void
     */
    /*function parentAssertValidURL($server) {
        parent::assertValidUrl($server);
    }*/

    /**
     * Used to return HTTP/1.1 403 Forbidden
     *
     * @return void
     */
    function forbiddenError() {
        header('HTTP/1.1 403 Forbidden: HTTPS required instead of HTTP');
        exit;
    }

}
?>