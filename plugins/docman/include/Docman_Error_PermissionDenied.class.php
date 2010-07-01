<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
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
require_once('common/error/Error_PermissionDenied.class.php');


class Docman_Error_PermissionDenied extends Error_PermissionDenied {
    
        /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        parent::__construct();
    }

    function getType() {
        return 'docman_permission_denied';
    }
    
    function getTextBase() {
        return 'plugin_docman';
    }
    

    /**
     * Returns the parameters needed to build interface 
     * according to the classe which makes the call
     * 
     * @return Array
     */
    function returnBuildInterfaceParam() {
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
     * the url sent to the project admin will be edited to "https://codendi.org/plugins/docman/?group_id=1564&action=details&id=96739"
     *
     * @parameter String $url
     *
     * @return String
     */
    function urlTransform($url) {
        $urlTransform = preg_replace('/action=show([^\d]|$)/', 'action=details$1', $url);
        if ($urlTransform) {
            return $urlTransform;
        } else {
            return $url;
        }
    }
    
    function returnURLData($urlData, $language) {
       //Add information about service 
       $urlData = ' "'.$this->urlTransform($urlData).'" ';
       $link = $urlData."  ".$language->getText('include_exit', 'data_type').' "'.$this->getServiceType($urlData).'"';
       return $link;
        
    }
}
?>