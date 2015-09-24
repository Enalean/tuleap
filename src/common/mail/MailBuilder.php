<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class MailBuilder {

    const TRUNCATED_SUBJECT_TEMPLATE = 'subject';
    const TRUNCATED_BODY_TEMPLATE    = 'body';

    /** @var TemplateRenderer */
    private $renderer;

    public function __construct(TemplateRendererFactory $template_factory) {
        $this->renderer = $template_factory->getRenderer(
            ForgeConfig::get('codendi_dir') .'/src/templates/mail/truncated'
        );
    }

    /**
     * @param Project $project
     * @param Notification $notification
     * @param MailEnhancer $mail_enhancer
     *
     * @return bool
     */
    public function buildAndSendEmail(Project $project, Notification $notification, MailEnhancer $mail_enhancer) {
        $sent_status = true;
        foreach ($notification->getEmails() as $email) {
            $mail = $this->buildEmail($project, $notification, $mail_enhancer, $email);
            $sent_status = $sent_status && $mail->send();
        }

        return $sent_status;
    }

    private function buildEmail(Project $project, Notification $notification, MailEnhancer $mail_enhancer, $email) {
        $mail = $this->getMailSender();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($email);

        if ($project->getTruncatedEmailsUsage()) {
            $presenter = new MailPresenter(
                $notification->getServiceName(),
                $notification->getGotoLink(),
                ForgeConfig::get('sys_fullname')
            );

            $mail->setSubject($this->renderer->renderToString(self::TRUNCATED_SUBJECT_TEMPLATE, $presenter));
            $mail->setBodyHtml($this->renderer->renderToString(self::TRUNCATED_BODY_TEMPLATE, $presenter));
        } else {
            if ($notification->hasHTMLBody()) {
                $mail->setBodyHtml($notification->getHTMLBody());
            }

            if ($notification->hasTextBody()) {
                $mail->setBodyText($notification->getTextBody());
            }

            $mail->setSubject($notification->getSubject());
            $mail_enhancer->enhanceMail($mail);
        }

        return $mail;
    }

    protected function getMailSender() {
        return new Codendi_Mail();
    }
}