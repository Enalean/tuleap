<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
     *
     * @var Tour[]
     */
    var $tours = array();
    
    /**
    * Constructor
    */
    public function Response() {
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

    function addTour(Tuleap_Tour $tour) {
        $this->tours[] = $tour;
    }

    /**
     * @return Tuleap_Tour[]
     */
    function getTours() {
        return $this->tours;
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

    public function getAndClearRawFeedback() {
        $feedback = $this->getRawFeedback();
        $this->clearFeedback();
        return $feedback;
    }

    /**
     * @return array of error messages
     */
    function getFeedbackErrors() {
        return $this->_feedback->fetchErrors();
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
        $cookie_manager->setHTTPOnlyCookie($name, $value, $expire);
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

    public function sendXMLAttachementFile($xml, $output_filename) {
        header ('Content-Description: File Transfer');
        header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header ('Content-Disposition: attachment; filename="'.$output_filename.'"');
        header ('Content-Type: application/xml');

        echo $xml;
    }

    public function send401UnauthorizedHeader() {
        header('HTTP/1.0 401 Unauthorized', true, 401);
    }

    public function send400JSONErrors($message) {
        header('Content-Type: application/json; charset=UTF-8', true);
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo json_encode($message);
        exit;
    }
}
