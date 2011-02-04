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
 * Check the URL validity (protocol, host name, query) regarding server constraints
 * (anonymous, user status, project privacy, ...) and manage redirection when needed  
 */
class URLVerification {

    protected $urlChunks = null;

    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        
    }

    /**
     * Returns an array containing data for the redirection URL
     *
     * @return Array
     */
    function getUrlChunks() {
        return $this->urlChunks;
    }

    /**
     * Returns the current user
     *
     * @return User
     */
    function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    public function getEventManager() {
        return EventManager::instance();
    }

    /**
     * Tests if the requested script name is allowed for anonymous or not
     *
     * @param Array $server
     *
     * @return Boolean
     */
    function isScriptAllowedForAnonymous($server) {
        // Defaults
        $allowedAnonymous['/current_css.php']            = true;
        $allowedAnonymous['/account/login.php']          = true;
        $allowedAnonymous['/account/register.php']       = true;
        $allowedAnonymous['/account/change_pw.php']      = true;
        $allowedAnonymous['/include/check_pw.php']       = true;
        $allowedAnonymous['/account/lostpw.php']         = true;
        $allowedAnonymous['/account/lostlogin.php']      = true;
        $allowedAnonymous['/account/lostpw-confirm.php'] = true;
        $allowedAnonymous['/account/pending-resend.php'] = true;
        $allowedAnonymous['/account/verify.php']         = true;
        $allowedAnonymous['/scripts/check_pw.js.php']    = true;
        if (isset($allowedAnonymous[$server['SCRIPT_NAME']]) && $allowedAnonymous[$server['SCRIPT_NAME']] == true) {
            return true;
        }

        // Site admin configuration
        if ($this->isUrlAllowedBySiteContent($server)) {
            return true;
        }

        // Plugins
        $anonymousAllowed = false;
        $params = array('script_name' => $server['SCRIPT_NAME'], 'anonymous_allowed' => &$anonymousAllowed);
        $this->getEventManager()->processEvent('anonymous_access_to_script_allowed', $params);

        return $anonymousAllowed;
    }

    /**
     * Allow to define whitlist URLs for anonymous by site admin in configuration
     *
     * @param Array $server
     *
     * @return Boolean
     */
    protected function isUrlAllowedBySiteContent($server) {
        $enable_anonymous_url = false;
        $allowed_scripts      = array();

        include($GLOBALS['Language']->getContent('include/allowed_url_anonymously','en_US'));
        if ($enable_anonymous_url) {
            foreach ($allowed_scripts as $script) {
                if (strcmp($server['SCRIPT_NAME'], $script) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return true if given request is using SSL
     *
     * @param Array $server
     *
     * @return Boolean
     */
    public function isUsingSSL($server) {
        return (isset($server['HTTPS']) && $server['HTTPS'] == 'on');
    }

    /**
     * Always permit requests for localhost, or for api or soap scripts
     *
     * @param Array $server
     *
     * @return Boolean
     */
    function isException($server) {

        return (($server['SERVER_NAME'] == 'localhost')
             || (strcmp(substr($server['SCRIPT_NAME'], 0, 5), '/api/') == 0)
             || (strcmp(substr($server['SCRIPT_NAME'], 0, 6), '/soap/') == 0));

    }

    /**
     * Tests if the server name is valid or not
     *
     * @param Array $server
     * @param String $host
     *
     * @return Boolean
     */
    function isValidServerName($server, $host) {

        return ($server['HTTP_HOST'] == $host);
    }

    /**
     * Returns the redirection URL from urlChunks
     *
     * This method returns the ideal URL to use to access a ressource. It doesn't
     * check if the URL is valid or not.
     * It conserves the same entree for protocol (i.e host or  request)  when it not has 
     * been modified by one of the methods dedicated to verify its validity.
     *
     * @param Array $server
     *
     * @return String
     */
    function getRedirectionURL($server) {
        $location = '';
        $chunks = $this->getUrlChunks($server);
        if (isset($chunks['protocol'])) {
            $location = $chunks['protocol']."://";
        } else {
            if ($this->isUsingSSL($server)) {
                $location = "https://";
            } else {
                $location = "http://";
            }
        }
            
            if (isset($chunks['host'])) {
                $location .= $chunks['host'];
            } else {
                $location .= $server['HTTP_HOST'];
            } 
            if (isset($chunks['script'])) {
                $location .= $chunks['script'];
            } else {
                $location  .= $server['REQUEST_URI'];
            } 
        return $location;
    }

    /**
     * Modify the protocol entry if needed
     *
     * @param Array $server
     *
     * @return void
     */
    public function verifyProtocol($server) {
        if (!$this->isUsingSSL($server)) {
            if ($GLOBALS['sys_force_ssl'] == 1) {
                $this->urlChunks['protocol'] = 'https';
            }
        }
    }

    /**
     * Modify the host name if needed
     *
     * @param Array $server
     *
     * @return void
     *
     */
    public function verifyHost($server) {
        if (!$this->isException($server)) {
            if ($this->isUsingSSL($server)) {
                if (!$this->isValidServerName($server, $GLOBALS['sys_https_host'])) {
                    $this->urlChunks['host'] = $GLOBALS['sys_https_host'];
                }
            } elseif ($GLOBALS['sys_force_ssl'] == 1) {
                $this->urlChunks['host'] = $GLOBALS['sys_https_host'];
            } elseif (!$this->isValidServerName($server, $GLOBALS['sys_default_domain'])) {
                $this->urlChunks['host'] = $GLOBALS['sys_default_domain'];
            }
        }
    }

    /**
     * Check if anonymous is granted to access else redirect to login page
     *
     * @param Array $server
     *
     * @return void
     */
    public function verifyRequest($server) {
        $user = $this->getCurrentUser();
        if (!$GLOBALS['sys_allow_anon'] && $user->isAnonymous() && !$this->isScriptAllowedForAnonymous($server)) {
            $returnTo = urlencode((($server['REQUEST_URI'] === "/")?"/my/":$server['REQUEST_URI']));
            $url = parse_url($server['REQUEST_URI']);
            if (isset($url['query'])) {
                $query = $url['query'];
                if (strstr($query, 'pv=2')) {
                    $returnTo .= "&pv=2";
                }
            }
            $this->urlChunks['script']   = '/account/login.php?return_to='.$returnTo;
        }
    }

    /**
     * Checks that a restricted user can access the requested URL.
     *
     * @param Array $server
     *
     * @return void
     */
    function checkRestrictedAccess($server) {
        if ($this->getCurrentUser()->isRestricted() &&
        !util_check_restricted_access($server['REQUEST_URI'], $server['SCRIPT_NAME'])) {
            exit_restricted_user_permission_denied();
        }
    }

    /**
     * Checks that registreded users but not members of a private project can't access to it.
     *
     * @param Array $server
     *
     * @return void
     */
    function checkPrivateAccess($server) {
        if ((strcmp(substr($server['SCRIPT_NAME'], 0, 5), '/api/') !=0)&&
        !util_check_private_access($server['REQUEST_URI'])) {
            exit_private_project_permission_denied();
        }
    }

    /**
     * Check URL is valid and redirect to the right host/url if needed.
     *
     * Force SSL mode if required except if request comes from localhost, or for api scripts
     *
     * Limit responsability of each method for sake of simplicity. For instance:
     * getRedirectionURL will not check all the server name or script name details
     * (localhost, api, etc). It only cares about generating the right URL.
     * 
     * @param Array $server
     *
     * @return void
     */
    public function assertValidUrl($server) {
        if (!$this->isException($server)) {
            $this->verifyProtocol($server);
            $this->verifyHost($server);
            $this->verifyRequest($server);
            $chunks = $this->getUrlChunks();
            if (isset($chunks)) {
                $location = $this->getRedirectionURL($server);
                $this->header($location);
            }
            $this->checkRestrictedAccess($server);
            $this->checkPrivateAccess($server);
        }
    }

    /**
     * Wrapper of header method
     *
     * @param String $location
     *
     * @return void
     */
    function header($location) {

        header('Location: '.$location);
        exit;

    }

}

?>