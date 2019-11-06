<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
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
require_once('Docman_PermissionsManager.class.php');
require_once('Docman_ItemFactory.class.php');


class Docman_Error_PermissionDenied extends Error_PermissionDenied
{

        /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct()
    {
        parent::__construct();
    }

    function getType()
    {
        return 'docman_permission_denied';
    }

    function getTextBase()
    {
        return 'plugin_docman';
    }


    /**
     * Returns the parameters needed to build interface
     * according to the classe which makes the call
     *
     * @return Array
     */
    function returnBuildInterfaceParam()
    {
        $param = array();
        $param['name']   = 'msg_docman_access';
        $param['func']   = 'docman_access_request';
        $param['action'] = '/plugins/docman/sendmessage.php';
        $param['index']  = 'docman_no_perm';
        return $param;
    }


    /**
     * It redirects the show action pointed with the document url  to its details section
     *
     * If user requires for istance the url  "https://codendi.org/plugins/docman/?group_id=1564&action=show&id=96739"
     * the url sent to the project admin will be edited to "https://codendi.org/plugins/docman/?group_id=1564&action=details&section=permissions&id=96739"
     *
     * @parameter String $url
     *
     * @return String
     */
    function urlTransform($url)
    {
        $query = $this->urlQueryToArray($url);
        if (!isset($query['action'])) {
            $url = $url.'&action=details&section=permissions';
        } else {
            if ($query['action'] == 'details') {
                if (!isset($query['section'])) {
                    $url = $url.'&section=permissions';
                } else {
                    // replace any existing section by 'permissions'
                    $url = preg_replace('/section=([^&]+|$)/', 'section=permissions', $url);
                }
            } else {
                $url = preg_replace('/action=show([^\d]|$)/', 'action=details&section=permissions$1', $url);
            }
        }
        return $url;
    }

    /**
     *  Returns the url after modifying it and add information about the concerned service
     *
     * @param String $urlData
     * @param BaseLanguage $language
     *
     * @return String
     */
    function getRedirectLink($urlData, $language)
    {
        return $this->urlTransform($urlData);
    }

    /**
     * Transform query part of string URL to an hashmap indexed by variables
     *
     * @param String $url The URL
     *
     * @return Array
     */
    function urlQueryToArray($url)
    {
        $params = array();
        $query  = explode('&', parse_url($url, PHP_URL_QUERY));
        foreach ($query as $tok) {
            list($var, $val) = explode('=', $tok);
            $params[$var] = urldecode($val);
        }
        return $params;
    }

    /**
     * Returns the docman manager list for given item
     *
     * @param Project $project
     * @param String $url
     *
     * @return Array
     */
    function extractReceiver($project, $url)
    {
        $query = $this->urlQueryToArray($url);
        if (isset($query['id'])) {
            $id = $query['id'];
        } else {
            if (isset($query['item'])) {
            } else {
                //if no item id is filled, we retieve the root id: the id of "Project documentation"
                if (isset($query['group_id'])) {
                    $itemFactory = $this->_getItemFactoryInstance($project->getId());
                    $res = $itemFactory->getRoot($project->getId());
                    if ($res !== null) {
                        $row = $res->toRow();
                        $query['id'] = $row['item_id'];
                    }
                }
            }
        }

        $pm = $this->_getPermissionManagerInstance($project->getId());
        $adminList = [];
        if (isset($query['id'])) {
            $adminList = $pm->getDocmanManagerUsers($query['id'], $project);
        }
        if (empty($adminList)) {
            $adminList = $pm->getDocmanAdminUsers($project);
        }
        if (empty($adminList)) {
            $adminList = $pm->getProjectAdminUsers($project);
        }
        $receivers = array();
        foreach ($adminList as $mail => $language) {
            $receivers[] = $mail;
        }
        return array('admins' => $receivers, 'status' => true);
    }

    /**
     * Wrapper for Docman_PermissionManager
     *
     * @return Docman_PermissionsManager
     */
    function _getPermissionManagerInstance($groupId)
    {
        return Docman_PermissionsManager::instance($groupId);
    }

    /**
     * Wrapper for Docman_ItemFactory
     *
     * @return Docman_ItemFactory
     */
    function _getItemFactoryInstance($groupId)
    {
        return Docman_ItemFactory::instance($groupId);
    }
}
