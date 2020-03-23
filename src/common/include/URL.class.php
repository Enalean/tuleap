<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class URL
{

    /**
    * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
    */
    /* static */ public function parse($url)
    {
        $components = array();
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i', $url, $components);
        return $components;
    }
    /* static */ public function getHost($url)
    {
        $components = URL::parse($url);
        return $components[4];
    }

    public static function getScheme($url)
    {
        $components = URL::parse($url);
        return $components[2];
    }
    /**
     *
     * Retreives the project name from svn request uri
     * @param String $uri
     *
     * @return String
     */
    public function getGroupNameFromSVNUrl($uri)
    {
        $pieces = explode('&', $uri);
        foreach ($pieces as $piece) {
            if (strpos($piece, 'root=') !== false) {
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
    public function getProjectNameRule()
    {
        return new Rule_ProjectName();
    }

    public function getGroupIdFromUrl($url)
    {
        $request = HTTPRequest::instance();
        $req_uri = '/' . trim($url, "/");
        // /projects/ and /viewvc/
        if ((strpos($req_uri, '/projects/') === 0) || (strpos($req_uri, '/viewvc.php/') !== false)) {
            $this_proj_name = '';
            if (strpos($req_uri, '/viewvc.php/') !== false) {
                $this_proj_name = $this->getGroupNameFromSVNUrl($req_uri);
            } elseif (strpos($req_uri, '/projects/') !== false) {
                $pieces = explode("/", $url);
                $this_proj_name = $pieces[2];
            }
            //Project short name validation
            $rule = $this->getProjectNameRule();
            if ($rule->containsIllegalChars($this_proj_name)) {
                return false;
            }
            $dao = $this->getProjectDao();
            $dao_results = $dao->searchByUnixGroupName($this_proj_name);
            if ($dao_results->rowCount() < 1) {// project does not exist
                return false;
            }
            $group_id = $dao_results->getRow();
            $group_id = $group_id['group_id'];
        }
        // Forum and news. Each published news is a special forum of project 'news'
        if (strpos($req_uri, '/forum/') === 0) {
            if (array_key_exists('forum_id', $_REQUEST) && $_REQUEST['forum_id']) {
                // Get corresponding project
                $dao = $this->getForumDao();
                $result = $dao->searchByGroupForumId($_REQUEST['forum_id']);
                $group_id = $result->getRow();
                $group_id = $group_id['group_id'];

                // News
                if ($group_id == $GLOBALS['sys_news_group']) {
                    $group_id = $this->getGroupIdForNewsFromForumId($_REQUEST['forum_id']);
                }
            }

            if (array_key_exists('msg_id', $_REQUEST) && $_REQUEST['msg_id']) {
                // Get corresponding project
                $dao = $this->getForumDao();
                $row = $dao->getMessageProjectIdAndForumId($_REQUEST['msg_id']);
                $group_id = $row['group_id'];
                $forum_id = $row['group_forum_id'];

                // News
                if ($group_id == $GLOBALS['sys_news_group']) {
                    // Otherwise, get group_id of corresponding news
                    $group_id = $this->getGroupIdForNewsFromForumId($forum_id);
                }
            }
        }

        // Artifact attachment download...
        if (strpos($req_uri, '/tracker/download.php') === 0) {
            if (isset($_REQUEST['artifact_id'])) {
                $dao = $this->getArtifactDao();
                $result = $dao->searchArtifactId($_REQUEST['artifact_id']);
                $group_id = $result->getRow();
                $group_id = $group_id['group_id'];
            }
        }

        EventManager::instance()->processEvent(
            Event::GET_PROJECTID_FROM_URL,
            array(
                'url'         => $req_uri,
                'project_id'  => &$group_id,
                'project_dao' => $this->getProjectDao(),
                'request'     => new Codendi_Request($_REQUEST)
            )
        );

        if (isset($group_id) && $group_id) {
            return $group_id;
        } elseif (isset($_REQUEST['group_id'])) {
            return $_REQUEST['group_id'];
        }

        return null;
    }

    private function getGroupIdForNewsFromForumId($forum_id)
    {
        $dao = $this->getNewsBytesDao();
        $result = $dao->searchByForumId($forum_id);
        $group_id = $result->getRow();
        return $group_id['group_id'];
    }

    public function getProjectDao()
    {
        return new ProjectDao(CodendiDataAccess::instance());
    }

    public function getForumDao()
    {
        return new ForumDao(CodendiDataAccess::instance());
    }

    public function getNewsBytesDao()
    {
        return new NewsBytesDao(CodendiDataAccess::instance());
    }

    public function getArtifactDao()
    {
        return new ArtifactDao(CodendiDataAccess::instance());
    }
}
