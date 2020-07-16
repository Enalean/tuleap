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

use Tracker;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImport;

class JiraSuccessImportNotifier
{
    /**
     * @var JiraImportNotifier
     */
    private $jira_import_notifier;
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
        JiraImportNotifier $jira_import_notifier,
        InstanceBaseURLBuilder $base_url_builder,
        LocaleSwitcher $locale_switcher,
        \TemplateRendererFactory $template_renderer_factory
    ) {
        $this->jira_import_notifier = $jira_import_notifier;
        $this->base_url_builder     = $base_url_builder;
        $this->locale_switcher           = $locale_switcher;

        $this->renderer = $template_renderer_factory->getRenderer(__DIR__);
    }

    public function warnUserAboutSuccess(
        PendingJiraImport $pending_jira_import,
        Tracker $tracker,
        JiraUserOnTuleapCache $jira_users_on_tuleap_cache
    ): void {
        $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $pending_jira_import->getUser()->getLocale(),
            function () use ($pending_jira_import, $tracker, $jira_users_on_tuleap_cache) {
                $project = $pending_jira_import->getProject();
                $subject = dgettext('tuleap-tracker', 'Jira import is finished');

                $link = $this->getLink($tracker);

                $presenter = new JiraSuccessImportNotificationPresenter(
                    $pending_jira_import,
                    $link,
                    $tracker,
                    $subject,
                    $jira_users_on_tuleap_cache
                );

                $this->jira_import_notifier->notify(
                    $subject,
                    $project,
                    $pending_jira_import,
                    $this->renderer->renderToString('notification-success-html', $presenter),
                    $this->renderer->renderToString('notification-success-text', $presenter),
                    $link,
                    $this->getAdditionalBreadcrumbs($tracker),
                );
            }
        );
    }

    private function getAdditionalBreadcrumbs(Tracker $tracker): array
    {
        $hp = \Codendi_HTMLPurifier::instance();

        return [
            '<a href="' . $this->getLink($tracker) . '">' . $hp->purify($tracker->getName()) . '</a>',
        ];
    }

    private function getLink(Tracker $tracker): string
    {
        $base_url = $this->base_url_builder->build();

        return $base_url . '/plugins/tracker/?' . http_build_query(['tracker' => $tracker->getId()]);
    }
}
