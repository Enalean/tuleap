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

    /**
     *
     * Retreives the project name from svn request uri
     * @param String $uri
     * 
     * @return String
     */
    function getGroupNameFromSVNUrl($uri) {
        $pieces = explode('&', $uri);
        foreach($pieces as $piece) {
            if(strpos($piece, 'root=') !== false) {
                $parts = $piece;
                break;
            }
        }
        if (!isset($parts)) {
            return false;
        }
        $group = explode('=', $parts);
        return $group[1];
    }

    /**
     * Wrapper for Rule_ProjectName
     */
    function getProjectNameRule() {
        return new Rule_ProjectName();
    }

    function getGroupIdFromUrl($url) {
        $req_uri='/'.trim($url, "/");
        // /projects/ and /viewvc/
        if ((strpos($req_uri,'/projects/') === 0)||(strpos($req_uri,'/viewvc.php/') !== false)) {
            if (strpos($req_uri,'/viewvc.php/') !== false) {
                $this_proj_name = $this->getGroupNameFromSVNUrl($req_uri);
            } else if (strpos($req_uri,'/projects/') !== false) {
                $pieces = explode("/", $url);
                $this_proj_name=$pieces[2];
            }
            //Project short name validation
            $rule = $this->getProjectNameRule();
            if ($rule->containsIllegalChars($this_proj_name)) {
                return false;
            }
            $dao = $this->getProjectDao();
            $dao_results=$dao->searchByUnixGroupName($this_proj_name);
            if ($dao_results->rowCount() < 1) {# project does not exist
                return false;
            }
            $group_id=$dao_results->getRow();
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

        if (strpos($req_uri,'/plugins/mediawiki/wiki/') === 0) {
            $pieces       = explode("/", $req_uri);
            $project_name = $pieces[4];
            
            $dao          = $this->getProjectDao();
            $dao_results  = $dao->searchByUnixGroupName($project_name);

            if ($dao_results->rowCount() < 1) {
                // project does not exist
                return false;
            }

            $project_data = $dao_results->getRow();
            $group_id     = $project_data['group_id'];
        }

        if (isset($group_id)) {
            return $group_id;
        } else return null;
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
