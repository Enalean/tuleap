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

require_once('common/dao/ProjectDao.class.php');
require_once('common/dao/ArtifactDao.class.php');
require_once('common/dao/ForumDao.class.php');
require_once('common/dao/NewsBytesDao.class.php');
require_once('common/event/EventManager.class.php');

class URL {

    /**
    * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
    */
    /* static */ function parse($url) {
        $components = array();
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i',$url, $components);
        return $components;
    }
    /* static */ function getHost($url) {
        $components = URL::parse($url);
        return $components[4];
    }
        
    function getGroupIdFromUrl($url) {
 
             $req_uri='/'.trim($url, "/");
             // /projects/ and /viewvc/
             if ((strpos($req_uri,'/projects/') === 0)||(strpos($req_uri,'/viewvc.php/') !== false)) {
           
                 if (strpos($req_uri,'/viewvc.php/') !== false) {
                     preg_match("/root=([a-zA-Z0-9_-]+)/",$req_uri, $matches);
                     $this_proj_name=$matches[1];
                 }else if (strpos($req_uri,'/projects/') !== false) {
                     $pieces = explode("/", $url);
                     $this_proj_name=$pieces[2];                     
                 }
                 
                 $dao = $this->getProjectDao();
                 $res_proj=$dao->searchByUnixGroupName($this_proj_name);
                 if ($res_proj->rowCount() < 1) {# project does not exist
                     return false;
                 }
                 $group_id=$res_proj->getRow();
                 $group_id=$group_id['group_id'];
             }
        
             // Forum and news. Each published news is a special forum of project 'news'
             if (strpos($req_uri,'/forum/') === 0) {
                 if (array_key_exists('forum_id', $_REQUEST) && $_REQUEST['forum_id']) {
                     // Get corresponding project
                     $dao = $this->getForumDao();
                     $result = $dao->searchByGroupForumId($_REQUEST['forum_id']);
                     $group_id=$result->getRow();
                     $group_id=$group_id['group_id'];
                    
                     // News
                     if ($group_id==$GLOBALS['sys_news_group']) {
                         // Otherwise, get group_id of corresponding news
                         $dao = $this->getNewsBytesDao();
                         $result = $dao->searchByForumId($_REQUEST['forum_id']);
                         $group_id = $result->getRow();
                         $group_id = $group_id['group_id'];
     
                     }
                 }
             }
             // File downloads. It might be a good idea to restrict access to shownotes.php too...
             if (strpos($req_uri,'/file/download.php') === 0) {
                 list(,$group_id, $file_id) = explode('/', $GLOBALS['PATH_INFO']);
             }

             // Artifact attachment download...
             if (strpos($req_uri,'/tracker/download.php') === 0) {
                 if (isset($_REQUEST['artifact_id'])) {
                     $dao = $this->getArtifactDao();
                     $result = $dao->searchArtifactId($_REQUEST['artifact_id']);
                     $group_id=$result->getRow();
                     $group_id=$group_id['group_id'];
                     
                 }
             }
        
         if(isset($group_id)) {
             return $group_id;
         }else return null;
    }

    /**
     * Return true if given request is using SSL
     * 
     * @param Array $server
     * 
     * @return true
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
             || (strcmp(substr($server['SCRIPT_NAME'],0,5),'/api/') == 0)
             || (strcmp(substr($server['SCRIPT_NAME'],0,6),'/soap/') == 0));

    }

    /**
     * Tests if the hostname is valid when HTTP is used
     *
     * @param Array $server
     * @param Array $allowedServerNames
     *
     * @return Boolean
     */
    function isValidServerName($server, $allowedServerNames, $host) {

        return (($server['HTTP_HOST'] == $host)
        || (isset($allowedServerNames[$server['SERVER_NAME']]) && $allowedServerNames[$server['SERVER_NAME']]));

    }

    /**
     * Tests if the hostname used to acess to Codendi is valid or not
     *
     * @param Array $server
     *
     * @return Boolean
     */
    public function isValidHost($server) {
        if ($this->isException($server)) {
            return true;
        } else {
            $em = $this->getEventManager();
            $allowedServerNames = array('localhost' => true);
            $em->processEvent('allowed_host', array('server_name' => &$allowedServerNames));

            if ($this->isUsingSSL($server)) {
                return $this->isValidServerName($server, $allowedServerNames, $GLOBALS['sys_https_host']);
            } elseif ($GLOBALS['sys_force_ssl'] == 1) {
                return false;
            } else {
                return $this->isValidServerName($server, $allowedServerNames, $GLOBALS['sys_default_domain']);
            }
        }

    }

    /**
     * Returns the redirection URL according to SSL parameters & config
     *
     * This method returns the ideal URL to use to access a ressource. It doesn't
     * check if the URL is valid or not.
     * 
     * @param Array $server
     *
     * @return String
     */
    function getRedirectionURL($server) {
        if ($GLOBALS['sys_force_ssl'] == 1 || $this->isUsingSSL($server)) {
            $location = "https://".$GLOBALS['sys_https_host'].$server['REQUEST_URI'];
        } else {
            $location = "http://".$GLOBALS['sys_default_domain'].$server['REQUEST_URI'];
        }
        return $location;

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
     */
    public function assertValidUrl($server) {
        if (!$this->isValidHost($server)) {
            $location = $this->getRedirectionURL($server);
            $this->header($location);
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

    function getProjectDao() {
        return new ProjectDao(CodendiDataAccess::instance());
    }
    
    function getForumDao() {
        return new ForumDao(CodendiDataAccess::instance());
    }
    
    function getNewsBytesDao() {
        return new NewsBytesDao(CodendiDataAccess::instance());
    }
    
     function getArtifactDao() {
        return new ArtifactDao(CodendiDataAccess::instance());
    }
    
    function getEventManager() {
        return EventManager::instance();
    }
}
?>
