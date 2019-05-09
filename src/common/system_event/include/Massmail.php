<?php
/**
 * Copyright (c) Enalean SAS - 2016-2018. All rights reserved
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

namespace Tuleap\SystemEvent;

use Codendi_HTMLPurifier;
use Codendi_Mail;
use ForgeConfig;
use SystemEvent;

/**
 * Send a massmail
 */
class Massmail extends SystemEvent
{

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        $parameters = $this->parseParamaters();

        return 'Destination: ' . $this->getDestinationLabel($parameters->destination) .
            ', Subject: ' . Codendi_HTMLPurifier::instance()->purify($parameters->subject);
    }

    /**
     * {
     *    'destination' => 'comm' | 'sf' | 'all' | 'admin' | 'sfadmin' | 'devel',
     *    'message'     => <string>,
     *    'subject'     => <string>
     * }
     */
    private function parseParamaters()
    {
        return json_decode($this->parameters);
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    public function process()
    {
        $parameters = $this->parseParamaters();
        $recipients = $this->searchRecipients($parameters->destination);
        if (! $recipients) {
            $this->error('Unknown destination');

            return false;
        }

        $has_success = false;
        $errors      = '';
        $mail        = $this->buildMail($parameters);

        $this->flood($mail, $recipients, $errors, $has_success);

        if ($has_success && $errors) {
            $this->warning('Some failed: ' . $errors);

            return false;
        }

        if (! $has_success && $errors) {
            $this->error('Could not send mail');

            return false;
        }

        $this->done();

        return true;
    }

    private function buildMail($parameters)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $title    = $purifier->purify($parameters->subject, CODENDI_PURIFIER_CONVERT_HTML);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->getLookAndFeelTemplate()->set('title', $title);
        $mail->setBodyHtml($parameters->message);
        $mail->setSubject($parameters->subject);

        return $mail;
    }

    private function flood(Codendi_Mail $mail, $recipients, &$errors, &$has_success)
    {
        $nb_rows = db_numrows($recipients);
        $tolist  = [];
        $noreply = ForgeConfig::get('sys_noreply');

        for ($i = 1; $i <= $nb_rows; $i++) {
            $tolist[] = db_result($recipients, $i - 1, 'email');
            if ($i % 25 == 0) {
                foreach ($tolist as $to) {
                    $mail->setBcc($to, true);
                }
                $mail->setTo($noreply, true);
                if ($mail->send()) {
                    $has_success = true;
                } else {
                    $errors .= $tolist;
                }
                usleep(2000000);
                $tolist = [];
            }
        }

        //send the last of the messages.
        if (count($tolist) > 0) {
            foreach ($tolist as $to) {
                $mail->setBcc($to, true);
            }
            $mail->setTo($noreply, true);
            if ($mail->send()) {
                $has_success = true;
            } else {
                $errors .= implode(', ', $tolist);
            }
        }
    }

    private function searchRecipients($destination)
    {
        switch ($destination) {
            case 'comm':
                $sql = "SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_va=1 GROUP BY lcase(email)";
                break;
            case 'sf':
                $sql = "SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) AND mail_siteupdates=1 GROUP BY lcase(email)";
                break;
            case 'all':
                $sql = "SELECT email,user_name FROM user WHERE ( status='A' OR status='R' ) GROUP BY lcase(email)";
                break;
            case 'admin':
                $sql = "SELECT user.email AS email,user.user_name AS user_name "
                    . "FROM user,user_group WHERE "
                    . "user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.admin_flags='A' "
                    . "GROUP by lcase(email)";
                break;
            case 'sfadmin':
                $sql = "SELECT user.email AS email,user.user_name AS user_name "
                    . "FROM user,user_group WHERE "
                    . "user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) AND user_group.group_id=1 "
                    . "GROUP by lcase(email)";
                break;
            case 'devel':
                $sql = "SELECT user.email AS email,user.user_name AS user_name "
                    . "FROM user,user_group WHERE "
                    . "user.user_id=user_group.user_id AND ( user.status='A' OR user.status='R' ) GROUP BY lcase(email)";
                break;
            default:
                return null;
        }

        return db_query($sql);
    }

    private function getDestinationLabel($destination)
    {
        $labels = array(
            'comm'    => 'Additional Community Mailings Subcribers',
            'sf'      => 'Site Updates Subcribers',
            'all'     => 'All Users',
            'admin'   => 'Project Administrators',
            'sfadmin' => ForgeConfig::get('sys_name') . ' Administrators',
            'devel'   => 'Project Developers',
        );

        return isset($labels[$destination]) ? $labels[$destination] : 'Unknown';
    }
}
