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
use Tracker;
use trackerPlugin;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Language\LocaleSwitcher;

class JiraSuccessImportNotifier
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

    public function warnUserAboutSuccess(PendingJiraImport $pending_jira_import, Tracker $tracker): void
    {
        $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $pending_jira_import->getUser()->getLocale(),
            function () use ($pending_jira_import, $tracker) {
                $hp       = \Codendi_HTMLPurifier::instance();
                $base_url = $this->base_url_builder->build();

                $project       = $pending_jira_import->getProject();
                $project_link  = $base_url . '/projects/' . urlencode($project->getUnixNameLowerCase());
                $trackers_link = $base_url . '/plugins/tracker/?' . http_build_query(['group_id' => $project->getID()]);
                $link          = $base_url . '/plugins/tracker/?' . http_build_query(['tracker' => $tracker->getId()]);

                $breadcrumbs = [
                    '<a href="' . $project_link . '">' . $hp->purify($project->getPublicName()) . '</a>',
                    '<a href="' . $trackers_link . '">' . dgettext('tuleap-tracker', 'Trackers') . '</a>',
                    '<a href="' . $link . '">' . $tracker->getName() . '</a>',
                ];

                $subject = dgettext('tuleap-tracker', 'Jira import is finished');

                $mail_enhancer = new MailEnhancer();
                $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
                $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));

                $presenter = [
                    'localized_created_on' => \DateHelper::formatForLanguage(
                        $pending_jira_import->getUser()->getLanguage(),
                        $pending_jira_import->getCreatedOn()->getTimestamp()
                    ),
                    'link'                 => $link,
                    'tracker_name'         => $tracker->getName(),
                    'title'                => $subject,
                ];

                $this->mail_notification_builder->buildAndSendEmail(
                    $project,
                    [$pending_jira_import->getUser()->getEmail()],
                    $subject,
                    $this->renderer->renderToString('notification-success-html', $presenter),
                    $this->renderer->renderToString('notification-success-text', $presenter),
                    $link,
                    trackerPlugin::TRUNCATED_SERVICE_NAME,
                    $mail_enhancer
                );
            }
        );
    }
}
