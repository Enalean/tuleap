<?php
/**
 * Copyright Enalean (c) 2014-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

class Tracker_Migration_MailLogger implements Logger
{

    /**
     * @var BackendLogger
     */
    private $backend_logger;

    /** @var Array */
    private $log_stack = array();

    public function __construct(BackendLogger $backend_logger)
    {
        $this->backend_logger = $backend_logger;
    }

    public function log($message, $level = Logger::INFO)
    {
        $this->log_stack[] = "[$level] $message";
    }

    public function debug($message)
    {
        $this->log($message, Logger::DEBUG);
    }

    public function info($message)
    {
        $this->log($message, Logger::INFO);
    }

    public function error($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException('<strong>'.$message.'</strong>', $e), Logger::ERROR);
    }

    public function warn($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Logger::WARN);
    }

    public function sendMail(PFUser $user, Project $project, $tv3_id, $tracker_name)
    {
        $mail        = new Codendi_Mail();
        $breadcrumbs = array();

        $breadcrumbs[] = '<a href="'. HTTPRequest::instance()->getServerUrl() .'/projects/'. $project->getUnixName(true) .'" />'. $project->getPublicName() .'</a>';

        $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
        $mail->addAdditionalHeader("X-Codendi-Project", $project->getUnixName());

        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setTo($user->getEmail());
        $mail->setSubject('Output of your migration TV3 -> TV5');

        $mail->setBody($this->getMailBody($tv3_id, $tracker_name));
        $mail->send();
        $this->purgeLogStack();
    }

    private function purgeLogStack()
    {
        unset($this->log_stack);
    }

    private function getMailBody($tv3_id, $tracker_name)
    {
        $html = '';

        $html .= "<h1> Here are the details (Warnings and Errors only) of your migration Tracker v3 #$tv3_id to $tracker_name</h1>";
        if (count($this->log_stack) > 0) {
            $html .= '<ul><li>'.implode('</li><li>', $this->log_stack).'</li></ul>';
        } else {
            $html .= "No error detected";
        }
        $html .= "<p>Done!<p>";

        return $html;
    }

    private function generateLogWithException($message, ?Exception $exception = null)
    {
        return $this->backend_logger->generateLogWithException($message, $exception);
    }
}
