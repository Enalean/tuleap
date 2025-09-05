<?php
/**
 * Copyright (c) Enalean SAS - 2016-Present. All rights reserved
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
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Mail\MailLogger;
use Tuleap\Massmail\RecipientUser;
use Tuleap\Massmail\RecipientUserDAO;
use Tuleap\Massmail\RecipientUsersRetriever;

/**
 * Send a massmail
 */
class Massmail extends SystemEvent
{
    #[FeatureFlagConfigKey('Feature flag to allow massmail feature to send each mails one by one.')]
    public const FEATURE_FLAG_KEY = 'send_massmail_one_by_one';

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    #[\Override]
    public function verbalizeParameters($with_link)
    {
        $parameters = $this->parseParamaters();

        return 'Destination: ' . $this->getDestinationLabel($parameters['destination']) .
            ', Subject: ' . Codendi_HTMLPurifier::instance()->purify($parameters['subject']);
    }

    /**
     * {
     *    'destination' => 'comm' | 'sf' | 'all' | 'admin' | 'sfadmin' | 'devel',
     *    'message'     => <string>,
     *    'subject'     => <string>
     * }
     *
     * @psalm-return array{destination:string, message:string, subject:string}
     */
    private function parseParamaters(): array
    {
        return json_decode($this->parameters, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    #[\Override]
    public function process()
    {
        $parameters = $this->parseParamaters();
        $recipients = $this->searchRecipients($parameters['destination']);
        if (! $recipients) {
            $this->error('Unknown destination');

            return false;
        }

        $has_success = false;
        $errors      = '';
        $mail        = $this->buildMail($parameters);

        $mail_logger = new MailLogger();
        if (ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY)) {
            $this->sendOneByOne($mail_logger, $parameters, $recipients, $errors, $has_success);
        } else {
            $this->flood($mail_logger, $mail, $recipients, $errors, $has_success);
        }

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

    /**
     * @psalm-param array{destination:string, message:string, subject:string} $parameters
     */
    private function buildMail(array $parameters): Codendi_Mail
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $title    = $purifier->purify($parameters['subject'], CODENDI_PURIFIER_CONVERT_HTML);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->getLookAndFeelTemplate()->set('title', $title);
        $mail->setBodyHtml($parameters['message']);
        $mail->setSubject($parameters['subject']);

        return $mail;
    }

    /**
     * @param RecipientUser[] $recipients
     * @psalm-param array{destination:string, message:string, subject:string} $parameters
     */
    private function sendOneByOne(
        MailLogger $mail_logger,
        array $parameters,
        array $recipients,
        string &$errors,
        bool &$has_success,
    ): void {
        $mail_logger->info('Send Massmail one by one');
        foreach ($recipients as $recipient) {
            $to_mail = $recipient->email;
            $mail_logger->debug('user email: ' . $to_mail);
            $mail = $this->buildMail($parameters);
            $mail->setTo($to_mail, true);
            if ($mail->send()) {
                $has_success = true;
            } else {
                $mail_logger->error("Error while sending massmail to $to_mail");
                $errors .= $to_mail;
            }
        }
    }

    /**
     * @param RecipientUser[] $recipients
     */
    private function flood(
        MailLogger $mail_logger,
        Codendi_Mail $mail,
        array $recipients,
        string &$errors,
        bool &$has_success,
    ): void {
        $mail_logger->info('Send Massmail in batch');
        $nb_rows = count($recipients);
        $tolist  = [];

        for ($i = 1; $i <= $nb_rows; $i++) {
            $tolist[] = $recipients[$i - 1]->email;
            if ($i % 25 == 0) {
                $mail_logger->debug('batch of emails: ' . implode(', ', $tolist));
                foreach ($tolist as $to) {
                    $mail->setBcc($to, true);
                }
                if ($mail->send()) {
                    $has_success = true;
                } else {
                    $errors .= implode(', ', $tolist);
                }
                usleep(2000000);
                $tolist = [];
            }
        }

        //send the last of the messages.
        if (count($tolist) > 0) {
            $mail_logger->debug('batch of emails: ' . implode(', ', $tolist));
            foreach ($tolist as $to) {
                $mail->setBcc($to, true);
            }
            if ($mail->send()) {
                $has_success = true;
            } else {
                $errors .= implode(', ', $tolist);
            }
        }
    }

    /**
     * @return RecipientUser[]
     */
    private function searchRecipients($destination): array
    {
        return (new RecipientUsersRetriever(new RecipientUserDAO()))->getRecipientUsers($destination);
    }

    private function getDestinationLabel(string $destination): string
    {
        $labels = [
            'comm'    => 'Additional Community Mailings Subscribers',
            'sf'      => 'Site Updates Subscribers',
            'all'     => 'All Users',
            'admin'   => 'Project Administrators',
            'sfadmin' => ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . ' Administrators',
            'devel'   => 'Project Developers',
        ];

        return isset($labels[$destination]) ? $labels[$destination] : 'Unknown';
    }
}
