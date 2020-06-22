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
        $subject = dgettext('tuleap-tracker', 'The artifact was not created');
        $message = sprintf(dgettext('tuleap-tracker', 'Artifact with the title "%1$s" was not created.'), $incoming_mail->getSubject());
        $message .= ' ' . dgettext('tuleap-tracker', 'Multiples users match your email, it must be unique on the platform to edit artifact by email.');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailNoUserMatch(IncomingMail $incoming_mail)
    {
        $to      = $this->getTo($incoming_mail);
        $subject = dgettext('tuleap-tracker', 'The artifact was not created');
        $message = sprintf(dgettext('tuleap-tracker', 'Artifact with the title "%1$s" was not created.'), $incoming_mail->getSubject());
        $message .= ' ' . dgettext('tuleap-tracker', 'Could not match an user with your email.');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailInsufficientPermissionCreation($to, $artifact_title)
    {
        $subject = dgettext('tuleap-tracker', 'The artifact was not created');
        $message = sprintf(dgettext('tuleap-tracker', 'Artifact with the title "%1$s" was not created.'), $artifact_title);
        $message .= ' ' . dgettext('tuleap-tracker', 'You do not have the necessary right to edit this artifact.');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailInsufficientPermissionUpdate($to, $artifact_id)
    {
        $subject = dgettext('tuleap-tracker', 'The artifact was not updated');
        $message = sprintf(dgettext('tuleap-tracker', 'Artifact #%1$s was not updated.'), $artifact_id);
        $message .= ' ' . dgettext('tuleap-tracker', 'You do not have the necessary right to edit this artifact.');
        $this->sendErrorMail($to, $subject, $message);
    }

    public function sendErrorMailTrackerGeneric(IncomingMail $incoming_mail)
    {
        $to      = $this->getTo($incoming_mail);
        $subject = dgettext('tuleap-tracker', 'The artifact was not created');
        $message = sprintf(dgettext('tuleap-tracker', 'Artifact with the title "%1$s" was not created.'), $incoming_mail->getSubject());
        $message .= ' ' . dgettext('tuleap-tracker', 'The tracker does not seem to have the create/reply by mail feature enabled.');
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
