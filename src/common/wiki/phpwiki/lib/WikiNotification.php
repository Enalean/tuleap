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

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Notification\Notification;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class WikiNotification
{
    /** @var Notification */
    private $notification;
    /** @var Project */
    private $project;
    /** @var MailEnhancer */
    private $mail_enhancer;

    public function __construct(array $emails, $wiki_name, $subject, $body, $goto_link, $project_id)
    {
        $project_manager     = ProjectManager::instance();
        $this->project       = $project_manager->getProject($project_id);
        $this->mail_enhancer = new MailEnhancer();

        $subject            = '[' . $wiki_name . '] ' . $subject;
        $this->notification = new Notification($emails, $subject, '', $body, $goto_link, 'Wiki');
    }

    /**
     * @return bool
     */
    public function send()
    {
        $mail_builder = new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(
                UserManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new MailLogger()
            )
        );

        return $mail_builder->buildAndSendEmail($this->project, $this->notification, $this->mail_enhancer);
    }
}
