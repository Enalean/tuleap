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

use Psr\Log\LogLevel;

class Tracker_Migration_MailLogger extends \Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface
{
    /** @var string[] */
    private $log_stack = [];

    public function __construct()
    {
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->log_stack[] = "[$level] " . $this->generateLogWithException($level, $message, $context);
    }

    private function generateLogWithException($level, string|\Stringable $message, array $context): string|\Stringable
    {
        $log_string = $message;
        if ($level === LogLevel::ERROR || $level === LogLevel::CRITICAL || $level === LogLevel::ALERT || $level === LogLevel::EMERGENCY) {
            $log_string = "<strong>$message</strong>";
        }
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exception     = $context['exception'];
            $error_message = $exception->getMessage();
            $stack_trace   = $exception->getTraceAsString();
            $log_string   .= ": $error_message:\n$stack_trace";
        }
        return $log_string;
    }

    public function sendMail(PFUser $user, Project $project, $tv3_id, $tracker_name)
    {
        $hp          = Codendi_HTMLPurifier::instance();
        $mail        = new Codendi_Mail();
        $breadcrumbs = [];

        $breadcrumbs[] = '<a href="' . \Tuleap\ServerHostname::HTTPSUrl() . '/projects/' . $project->getUnixName(true) . '" />' . $hp->purify($project->getPublicName()) . '</a>';

        $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
        $mail->addAdditionalHeader("X-Codendi-Project", $project->getUnixName());

        $mail->setFrom(ForgeConfig::get('sys_noreply'));
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
            $html .= '<ul><li>' . implode('</li><li>', $this->log_stack) . '</li></ul>';
        } else {
            $html .= "No error detected";
        }
        $html .= "<p>Done!<p>";

        return $html;
    }
}
