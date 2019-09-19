<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;

class Tracker_Artifact_MailGateway_Notifier
{
    private function sendErrorMail($to, $subject, $message)
    {
        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setBody($message);
        $mail->send();
    }

    public function sendErrorMailMultipleUsers(IncomingMail $incoming_mail)
    {
        $to      = $this->getTo($incoming_mail);
        $subject = $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'creation_error');
        $message = $GLOBALS['Language']->getText(
            'plugin_tracker_emailgateway',
            'artifact_error_name',
            $incoming_mail->getSubject()
        );
        $message .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'multiple_users');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailNoUserMatch(IncomingMail $incoming_mail)
    {
        $to      = $this->getTo($incoming_mail);
        $subject = $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'creation_error');
        $message = $GLOBALS['Language']->getText(
            'plugin_tracker_emailgateway',
            'artifact_error_name',
            $incoming_mail->getSubject()
        );
        $message .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'unknown_user');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailInsufficientPermissionCreation($to, $artifact_title)
    {
        $subject = $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'creation_error');
        $message = $GLOBALS['Language']->getText(
            'plugin_tracker_emailgateway',
            'artifact_error_name',
            array($artifact_title)
        );
        $message .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'insufficient_permission');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailInsufficientPermissionUpdate($to, $artifact_id)
    {
        $subject = $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'update_error');
        $message = $GLOBALS['Language']->getText(
            'plugin_tracker_emailgateway',
            'artifact_error_update_id',
            array($artifact_id)
        );
        $message .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'insufficient_permission');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailTrackerGeneric(IncomingMail $incoming_mail)
    {
        $to      = $this->getTo($incoming_mail);
        $subject = $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'creation_error');
        $message = $GLOBALS['Language']->getText(
            'plugin_tracker_emailgateway',
            'artifact_error_name',
            $incoming_mail->getSubject()
        );
        $message .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'tracker_misconfiguration');
        $this->sendErrorMail($to, $subject, $message);
    }

    private function getTo(IncomingMail $incoming_mail)
    {
        $from_addresses = $incoming_mail->getFrom();
        if (count($from_addresses) < 1) {
            return '';
        }
        return $from_addresses[0];
    }
}
