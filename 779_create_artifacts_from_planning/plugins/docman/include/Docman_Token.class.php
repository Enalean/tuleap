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


require_once('common/dao/CodendiDataAccess.class.php');
require_once('common/user/UserManager.class.php');
require_once('Docman_TokenDao.class.php');

/**
 *  Docman_Token 
 */
class Docman_Token {
    var $tok;
    /**
     * Generate a random token for the current user.
     * This token is stored with the referer.
     * @return the generated 
     */
    function Docman_Token() {
        $tok     = null;
        $user_id = $this->_getCurrentUserId();
        $referer = $this->_getReferer();
        $request =& $this->_getHTTPRequest();
        if ($referer && $user_id) {
            $url = parse_url($referer);
            if (isset($url['query'])) {
                parse_str($url['query'], $args);
                //valid referers : Pages without action =>
                // Embed, Browse, History, Properties
                $is_valid = isset($args['action']) && (
                    $args['action'] == 'show' //Browse & Embed
                    ||
                    (
                        $args['action'] == 'details'
                        &&
                        (
                            !isset($args['section']) //Properties
                            ||
                            $args['section'] == 'history' //History
                        )
                    )
                );
                if ($is_valid) {
                    $this->tok = md5(uniqid(rand(), true));
                    $dao =& $this->_getDao();
                    $dao->create($user_id, $this->tok, $referer);
                }
            }
        }
    }
    /* static */ function retrieveUrl($token) {
        $url = null;
        $um =& UserManager::instance();
        $dao =& new Docman_TokenDao(CodendiDataAccess::instance());
        $user =& $um->getCurrentUser();
        $user_id = $user->getId();
        if ($user_id) {
            $dar = $dao->searchUrl($user_id, $token);
            if ($dar && $dar->valid()) {
                $row = $dar->current();
                $url = $row['url'];
                $dao->delete($user_id, $token);
            }
        }
        return $url;
    }
    
    function getToken() {
        return $this->tok;
    }
    function &_getDao() {
        $d = new Docman_TokenDao(CodendiDataAccess::instance());
        return $d;
    }
    function _getReferer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }
    function _getCurrentUserId() {
        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        return $user->isAnonymous() ? null : $user->getId();
    }
    function &_getHTTPRequest() {
        return HTTPRequest::instance();
    }
}


?>