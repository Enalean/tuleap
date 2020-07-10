<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier;

use MailEnhancer;
use MailNotificationBuilder;
use trackerPlugin;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImport;

class JiraImportNotifier
{
    /**
     * @var InstanceBaseURLBuilder
     */
    private $base_url_builder;
    /**
     * @var MailNotificationBuilder
     */
    private $mail_notification_builder;

    public function __construct(
        InstanceBaseURLBuilder $base_url_builder,
        MailNotificationBuilder $mail_notification_builder
    ) {
        $this->base_url_builder          = $base_url_builder;
        $this->mail_notification_builder = $mail_notification_builder;
    }

    public function notify(
        string $subject,
        \Project $project,
        PendingJiraImport $pending_jira_import,
        string $html_body,
        string $text_body,
        string $link,
        array $additional_breadcrumbs
    ): void {
        $hp       = \Codendi_HTMLPurifier::instance();
        $base_url = $this->base_url_builder->build();

        $project_link  = $base_url . '/projects/' . urlencode($project->getUnixNameLowerCase());
        $trackers_link = $base_url . '/plugins/tracker/?' . http_build_query(['group_id' => $project->getID()]);

        $breadcrumbs = array_merge(
            [
                '<a href="' . $project_link . '">' . $hp->purify($project->getPublicName()) . '</a>',
                '<a href="' . $trackers_link . '">' . dgettext('tuleap-tracker', 'Trackers') . '</a>',
            ],
            $additional_breadcrumbs
        );

        $mail_enhancer = new MailEnhancer();
        $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
        $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));

        $this->mail_notification_builder->buildAndSendEmail(
            $project,
            [$pending_jira_import->getUser()->getEmail()],
            $subject,
            $html_body,
            $text_body,
            $link,
            trackerPlugin::TRUNCATED_SERVICE_NAME,
            $mail_enhancer
        );
    }
}
