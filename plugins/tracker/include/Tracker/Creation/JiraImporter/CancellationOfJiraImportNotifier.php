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

namespace Tuleap\Tracker\Creation\JiraImporter;

use MailEnhancer;
use MailNotificationBuilder;
use trackerPlugin;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Language\LocaleSwitcher;

class CancellationOfJiraImportNotifier
{
    /**
     * @var MailNotificationBuilder
     */
    private $mail_notification_builder;
    /**
     * @var InstanceBaseURLBuilder
     */
    private $base_url_builder;
    /**
     * @var LocaleSwitcher
     */
    private $locale_switcher;
    /**
     * @var \MustacheRenderer|\TemplateRenderer
     */
    private $renderer;

    public function __construct(
        MailNotificationBuilder $mail_notification_builder,
        InstanceBaseURLBuilder $base_url_builder,
        LocaleSwitcher $locale_switcher,
        \TemplateRendererFactory $template_renderer_factory
    ) {
        $this->mail_notification_builder = $mail_notification_builder;
        $this->base_url_builder          = $base_url_builder;
        $this->locale_switcher           = $locale_switcher;

        $this->renderer = $template_renderer_factory->getRenderer(__DIR__);
    }

    public function warnUserAboutDeletion(PendingJiraImport $pending_jira_import): void
    {
        $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $pending_jira_import->getUser()->getLocale(),
            function () use ($pending_jira_import) {
                $hp       = \Codendi_HTMLPurifier::instance();
                $base_url = $this->base_url_builder->build();

                $project = $pending_jira_import->getProject();
                $link    = $base_url . '/plugins/tracker?' . http_build_query(['group_id' => $project->getID()]);

                $breadcrumbs = [
                    '<a href="' . $base_url . '/projects/' . urlencode($project->getUnixNameLowerCase()) . '" />' .
                    $hp->purify($project->getPublicName()) .
                    '</a>',
                    '<a href="' . $link . '" />' . dgettext('tuleap-tracker', 'Trackers') . '</a>',
                ];

                $subject = dgettext('tuleap-tracker', 'Cancellation of your Jira import');

                $mail_enhancer = new MailEnhancer();
                $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
                $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));

                $presenter = [
                    'localized_created_on' => \DateHelper::formatForLanguage(
                        $pending_jira_import->getUser()->getLanguage(),
                        $pending_jira_import->getCreatedOn()->getTimestamp()
                    ),
                    'jira_server'          => $pending_jira_import->getJiraServer(),
                    'jira_project_id'      => $pending_jira_import->getJiraProjectId(),
                    'jira_issue_type_name' => $pending_jira_import->getJiraIssueTypeName(),
                    'tracker_name'         => $pending_jira_import->getTrackerName(),
                    'tracker_shortname'    => $pending_jira_import->getTrackerShortname(),
                ];

                $this->mail_notification_builder->buildAndSendEmail(
                    $project,
                    [$pending_jira_import->getUser()->getEmail()],
                    $subject,
                    $this->renderer->renderToString('notification-cancel-html', $presenter),
                    $this->renderer->renderToString('notification-cancel-text', $presenter),
                    $link,
                    trackerPlugin::TRUNCATED_SERVICE_NAME,
                    $mail_enhancer
                );
            }
        );
    }
}
