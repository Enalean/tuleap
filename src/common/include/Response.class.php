<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

/**
 * Response
 */
class Response
{

    /**
     *
     * @var Feedback
     */
    public $_feedback;

    /**
     *
     * @var Tour[]
     */
    public $tours = array();

    /**
    * Constructor
    */
    public function __construct()
    {
        $session_id = UserManager::instance()->getCurrentUser()->getSessionId();
        if ($session_id) {
            $dao = $this->getFeedbackDao();
            $dar = $dao->search($session_id);
            if ($dar && $dar->valid()) {
                $row             = $dar->current();
                $this->_feedback = new Feedback();
                $feedback_logs   = json_decode($row['feedback']);
                foreach ($feedback_logs as $feedback_log) {
                    $this->_feedback->log($feedback_log->level, $feedback_log->msg, $feedback_log->purify);
                }
                $dao->delete($session_id);
            }
        }
        if (!$this->_feedback) {
            $this->clearFeedback();
        }
    }

    public function addTour(Tuleap_Tour $tour)
    {
        $this->tours[] = $tour;
    }

    /**
     * @return Tuleap_Tour[]
     */
    public function getTours()
    {
        return $this->tours;
    }

    public function addFeedback($level, $message, $purify = CODENDI_PURIFIER_CONVERT_HTML)
    {
        $this->_feedback->log($level, $message, $purify);
    }

    /**
     * Only adds to the feedback if the messge doesn't already exist.
     */
    public function addUniqueFeedback($level, $message, $purify = CODENDI_PURIFIER_CONVERT_HTML)
    {
        if (! strstr($this->getRawFeedback(), $message)) {
            $this->_feedback->log($level, $message, $purify);
        }
    }

    public function displayFeedback()
    {
        $this->_feedback->display();
    }
    public function feedbackHasWarningsOrErrors()
    {
        return $this->_feedback->hasWarningsOrErrors();
    }
    public function feedbackHasErrors()
    {
        return $this->_feedback->hasErrors();
    }

    public function getRawFeedback()
    {
        return $this->_feedback->fetchAsPlainText();
    }

    public function getAndClearRawFeedback()
    {
        $feedback = $this->getRawFeedback();
        $this->clearFeedback();
        return $feedback;
    }

    /**
     * @return array of error messages
     */
    public function getFeedbackErrors()
    {
        return $this->_feedback->fetchErrors();
    }

    public function clearFeedback()
    {
        $this->_feedback = new Feedback();
    }

    public function clearFeedbackErrors()
    {
        return $this->_feedback->clearErrors();
    }

    private function getFeedbackDao()
    {
        return new FeedbackDao();
    }

    public function _serializeFeedback()
    {
        $dao        = $this->getFeedbackDao();
        $session_id = UserManager::instance()->getCurrentUser()->getSessionId();
        $dao->create($session_id, $this->_feedback->getLogs());
    }

    public function sendStatusCode($code)
    {
        header("HTTP/1.0 $code");
        echo $this->getRawFeedback();
    }

    public function setContentType($content_type)
    {
        header('Content-type: ' . $content_type);
    }

    public function sendJSON($content)
    {
        $this->setContentType('application/json');
        echo json_encode($content);
    }

    public function sendXMLAttachementFile($xml, $output_filename)
    {
        header('Content-Description: File Transfer');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Disposition: attachment; filename="' . $output_filename . '"');
        header('Content-Type: application/xml');

        echo $xml;
    }

    public function send401UnauthorizedHeader()
    {
        header('HTTP/1.0 401 Unauthorized', true, 401);
    }

    public function send400JSONErrors($message)
    {
        header('Content-Type: application/json; charset=UTF-8', true);
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo json_encode($message);
        exit;
    }

    public function permanentRedirect($redirect_url)
    {
        header("Location: $redirect_url", true, 301);
        exit;
    }
}
