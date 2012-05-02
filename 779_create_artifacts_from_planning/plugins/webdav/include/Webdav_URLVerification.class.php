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
     * Assume it's an url to be taken into account by this class. The conditions are:
     * - The used host is defined as webdav host
     * - The webdav host is different of default host (defined by sys_default_domain or sys_https_host)
     *
     * For the second point, this is to avoid the webdav URL checker override
     * default url checker for the web part. For instance, if sys_default_domain is example.com
     * and webdav host is also example.com, the webdav url verification will be used to test
     * access to example.com/tracker/... instead of default url checker.
     *
     * @see URLVerification#assertValidUrl($server)
     *
     * @param Array $server
     *
     * @return void
     */
    public function assertValidUrl($server) {
        if (strcmp($server['HTTP_HOST'], $this->getWebDAVHost()) == 0 
            && strcmp($this->getWebDAVHost(), $GLOBALS['sys_default_domain']) != 0
            && strcmp($this->getWebDAVHost(), $GLOBALS['sys_https_host']) != 0
            ) {
            if (!$this->isUsingSSL($server) && $GLOBALS['sys_force_ssl'] == 1) {
                $this->forbiddenError();
            }
        } else {
            parent::assertValidUrl($server);
        }
    }

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