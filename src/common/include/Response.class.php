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

require_once('common/include/Feedback.class.php');
require_once('common/dao/FeedbackDao.class.php');
require_once('common/include/CookieManager.class.php');

/**
 * Response
 */
class Response {
    
    var $_feedback;
    
    /**
    * Constructor
    */
    function Response() {
        if (session_hash()) {
            $dao =& $this->_getFeedbackDao();
            $dar =& $dao->search(session_hash());
            if ($dar && $dar->valid()) {
                $row = $dar->current();
                $this->_feedback = unserialize($row['feedback']);
                $dao->delete(session_hash());
            }
        }
        if (!$this->_feedback) {
            $this->_feedback =& new Feedback();
        }
    }
    function addFeedback($level, $message,  $purify=CODENDI_PURIFIER_CONVERT_HTML) {
        $this->_feedback->log($level, $message, $purify);
    }
    public function displayFeedback() {
        $this->_feedback->display();
    }
    function feedbackHasWarningsOrErrors() {
        return $this->_feedback->hasWarningsOrErrors();
    }
    function feedbackHasErrors() {
        return $this->_feedback->hasErrors();
    }
    function getRawFeedback() {
        return $this->_feedback->fetchAsPlainText();
    }
    function &_getFeedbackDao() {
        $f =& new FeedbackDao(CodendiDataAccess::instance());
        return $f;
    }
    function _serializeFeedback() {
        $dao =& $this->_getFeedbackDao();
        $dao->create(session_hash(), serialize($this->_feedback));
    }
    function setCookie($name, $value, $expire = 0) {
        $cookie_manager =& new CookieManager();
        $cookie_manager->setCookie($name, $value, $expire);
    }
    function removeCookie($name) {
        $cookie_manager =& new CookieManager();
        $cookie_manager->removeCookie($name);
    }
    public function sendStatusCode($code) {
        header("HTTP/1.0 $code");
        echo $this->getRawFeedback();
    }
}
?>
