<?php
require_once('common/include/Feedback.class.php');
require_once('common/dao/FeedbackDao.class.php');
require_once('common/include/CookieManager.class.php');
/**
* Response
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
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
    function addFeedback($level, $message) {
        $this->_feedback->log($level, $message);
    }
    function feedbackHasWarningsOrErrors() {
    	   return $this->_feedback->hasWarningsOrErrors();
    }
    function getRawFeedback() {
    	   return $this->_feedback->fetchAsPlainText();
    }
    function &_getFeedbackDao() {
        return new FeedbackDao(CodexDataAccess::instance());
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
}
?>
