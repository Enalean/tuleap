<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Tracker_Migration_MigrationLogger implements Logger {

    /** @var BackendLogger  */
    private $logger;

    /** @var Array */
    private $log_stack = array();

    public function __construct(BackendLogger $logger) {
        $this->logger = $logger;
    }

    public function log($message, $level = Feedback::INFO) {
        $log_line          = date('c')." [$level] $message\n";
        $this->log_stack[] = $log_line;

        $this->logger->log($message, $level);
    }

    public function debug($message) {
        $this->log($message, Feedback::DEBUG);
    }

    public function info($message) {
        $this->log($message, Feedback::INFO);
    }

    public function error($message, Exception $e = null) {
        $this->log($this->generateLogWithException($message, $e), Feedback::ERROR);
    }

    public function warn($message, Exception $e = null) {
        $this->log($this->generateLogWithException($message, $e), Feedback::WARN);

    }

    public function generateLogWithException($message, Exception $e = null) {
        return $this->logger->generateLogWithException($message, $e);
    }

    public function sendMail(PFUser $user, Project $project, $tv3_id, $tracker_name) {
        $mail        = new Codendi_Mail();
        $breadcrumbs = array();

        $breadcrumbs[] = '<a href="'. get_server_url() .'/projects/'. $project->getUnixName(true) .'" />'. $project->getPublicName() .'</a>';

        $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
        $mail->addAdditionalHeader("X-Codendi-Project", $project->getUnixName());

        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo($user->getEmail());
        $mail->setSubject('Output of your migration TV3 -> TV5');

        $mail->setBody($this->getMailBody($tv3_id, $tracker_name));
        $mail->send();
        $this->purgeLogStack();
    }

    private function purgeLogStack() {
        unset($this->log_stack);
    }

    private function getMailBody($tv3_id, $tracker_name) {
        $html = '';

        $html .= "<h1> Here are the details of your migration Tracker v3 #$tv3_id to $tracker_name</h1>";
        $html .= implode('<br />', $this->log_stack);

        return $html;
    }
}