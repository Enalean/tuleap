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

use Tuleap\Date\DateHelper;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\ServerHostname;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImport;

class JiraErrorImportNotifier
{
    /**
     * @var JiraImportNotifier
     */
    private $jira_import_notifier;
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
        LocaleSwitcher $locale_switcher,
        \TemplateRendererFactory $template_renderer_factory,
    ) {
        $this->jira_import_notifier = $jira_import_notifier;
        $this->locale_switcher      = $locale_switcher;

        $this->renderer = $template_renderer_factory->getRenderer(__DIR__);
    }

    public function warnUserAboutError(PendingJiraImport $pending_jira_import, string $message): void
    {
        $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $pending_jira_import->getUser()->getLocale(),
            function () use ($pending_jira_import, $message) {
                $project = $pending_jira_import->getProject();
                $subject = dgettext('tuleap-tracker', 'Error in your Jira import');

                $presenter = [
                    'localized_created_on' => DateHelper::formatForLanguage(
                        $pending_jira_import->getUser()->getLanguage(),
                        $pending_jira_import->getCreatedOn()->getTimestamp()
                    ),
                    'jira_server'          => $pending_jira_import->getJiraServer(),
                    'jira_project_id'      => $pending_jira_import->getJiraProjectId(),
                    'jira_issue_type_name' => $pending_jira_import->getJiraIssueTypeName(),
                    'jira_issue_type_id'   => $pending_jira_import->getJiraIssueTypeId(),
                    'tracker_name'         => $pending_jira_import->getTrackerName(),
                    'tracker_shortname'    => $pending_jira_import->getTrackerShortname(),
                    'message'              => $message,
                    'title'                => $subject,
                ];

                $this->jira_import_notifier->notify(
                    $subject,
                    $project,
                    $pending_jira_import,
                    $this->renderer->renderToString('notification-error-html', $presenter),
                    $this->renderer->renderToString('notification-error-text', $presenter),
                    $this->getLink($project),
                    []
                );
            }
        );
    }

    private function getLink(\Project $project): string
    {
        return ServerHostname::HTTPSUrl() . '/plugins/tracker?' . http_build_query(['group_id' => $project->getID()]);
    }
}
