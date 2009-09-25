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
             if ((strpos($req_uri,'/projects/') !== false)||(strpos($req_uri,'/viewvc.php/') !== false)) {
           
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
             if (strpos($req_uri,'/forum/') !== false) {
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
             if (strpos($req_uri,'/file/download.php') !== false) {
                 list(,$group_id, $file_id) = explode('/', $GLOBALS['PATH_INFO']);
             }

             // Artifact attachment download...
             if (strpos($req_uri,'/tracker/download.php') !== false) {
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
}
?>
