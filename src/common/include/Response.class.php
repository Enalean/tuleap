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
    
    /**
     *
     * @var Feedback
     */
    var $_feedback;
    
    /**
    * Constructor
    */
    function Response() {
        if (session_hash()) {
            $dao = $this->getFeedbackDao();
            $dar = $dao->search(session_hash());
            if ($dar && $dar->valid()) {
                $row = $dar->current();
                $this->_feedback = unserialize($row['feedback']);
                $dao->delete(session_hash());
            }
        }
        if (!$this->_feedback) {
            $this->clearFeedback();
        }
    }
    function addFeedback($level, $message,  $purify=CODENDI_PURIFIER_CONVERT_HTML) {
        $this->_feedback->log($level, $message, $purify);
    }
    
    /**
     * Only adds to the feedback if the messge doesn't already exist.
     */
    function addUniqueFeedback($level, $message,  $purify=CODENDI_PURIFIER_CONVERT_HTML) { 
        if(! strstr($this->getRawFeedback(), $message)) {
            $this->_feedback->log($level, $message, $purify);
         }
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

    public function clearFeedback() {
        $this->_feedback = new Feedback();
    }

    private function getFeedbackDao() {
        return new FeedbackDao();
    }

    function _serializeFeedback() {
        $dao = $this->getFeedbackDao();
        $dao->create(session_hash(), serialize($this->_feedback));
    }

    function setCookie($name, $value, $expire = 0) {
        $cookie_manager = new CookieManager();
        $cookie_manager->setCookie($name, $value, $expire);
    }

    function removeCookie($name) {
        $cookie_manager = new CookieManager();
        $cookie_manager->removeCookie($name);
    }

    public function sendStatusCode($code) {
        header("HTTP/1.0 $code");
        echo $this->getRawFeedback();
    }

    public function setContentType($content_type) {
        header('Content-type: ' . $content_type);
    }

    public function sendJSON($content) {
        $this->setContentType('application/json');
        echo json_encode($content);
    }

    /**
     * Send 401 Unauthorized and exit if the client asks for something else than text/html
     *
     * Please note that the negociation is hard coded with the script 'project_home'. 
     * This variable may need to be passed in parameters for others urls with content 
     * negotiation. Keep it as is for now.
     */
    public function send401UnauthorizedHeader() {
        header('HTTP/1.0 401 Unauthorized', true, 401);
        $default_content_type = 'text/html';
        $script               = 'project_home';
        $content_type         = util_negociate_alternate_content_types($script, $default_content_type);
        if ($content_type != $default_content_type) {
            exit;
        }
    }
}
?>
