<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Notification\Notification;

class MailNotificationBuilder // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * @var MailBuilder
     */
    private $mail_builder;

    public function __construct(MailBuilder $mail_builder)
    {
        $this->mail_builder = $mail_builder;
    }

    /**
     * @param array $emails
     * @param $subject
     * @param $html_body
     * @param $text_body
     * @param $link
     * @param $truncated_service_name
     *
     * @return bool
     */
    public function buildAndSendEmail(
        Project $project,
        array $emails,
        $subject,
        $html_body,
        $text_body,
        $link,
        $truncated_service_name,
        MailEnhancer $mail_enhancer,
    ) {
        return $this->mail_builder->buildAndSendEmail(
            $project,
            $this->getNotification(
                $emails,
                $subject,
                $html_body,
                $text_body,
                $link,
                $truncated_service_name
            ),
            $mail_enhancer
        );
    }

    /**
     * @param array $emails
     * @param $subject
     * @param $html_body
     * @param $text_body
     * @param $link
     * @param $truncated_service_name
     *
     * @return Notification
     */
    private function getNotification(array $emails, $subject, $html_body, $text_body, $link, $truncated_service_name)
    {
        return new Notification($emails, $subject, $html_body, $text_body, $link, $truncated_service_name);
    }
}
