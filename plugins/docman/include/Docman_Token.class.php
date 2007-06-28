<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('common/dao/CodexDataAccess.class.php');
require_once('common/include/UserManager.class.php');
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
        if ($referer && $user_id && !$request->get('bc')) {
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
        $dao =& new Docman_TokenDao(CodexDataAccess::instance());
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
        return new Docman_TokenDao(CodexDataAccess::instance());
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