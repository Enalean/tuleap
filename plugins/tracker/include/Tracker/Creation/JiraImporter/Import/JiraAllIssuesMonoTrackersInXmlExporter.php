<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\XML\JiraXMLNodeBuilder;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\XMLTracker;

class JiraAllIssuesMonoTrackersInXmlExporter implements JiraAllIssuesInXmlExporter
{
    public const MONO_TRACKER_NAME = 'Issues';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly JiraIssuesFromProjectInMonoTrackerInXmlExporter $issues_from_project_in_mono_tracker_in_xml_exporter,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public static function build(
        JiraClient $wrapper,
        LoggerInterface $logger,
        JiraUserOnTuleapCache $jira_user_on_tuleap_cache,
    ): self {
        return new self(
            $logger,
            JiraIssuesFromProjectInMonoTrackerInXmlExporter::build(
                $wrapper,
                $logger,
                $jira_user_on_tuleap_cache,
            ),
        );
    }

    /**
     * @param IssueType[] $jira_issue_types
     *
     * @throws JiraConnectionException
     */
    public function exportAllProjectIssuesToXml(
        \SimpleXMLElement $trackers_xml,
        PlatformConfiguration $jira_platform_configuration,
        string $jira_base_url,
        string $jira_project_key,
        array $jira_issue_types,
        IDGenerator $field_id_generator,
        LinkedIssuesCollection $linked_issues_collection,
        string $import_mode,
    ): void {
        $this->logger->info(sprintf("Import all issues in mono tracker %s", self::MONO_TRACKER_NAME));

        $tracker_itemname = TrackerCreationDataChecker::getShortNameWithValidFormat(self::MONO_TRACKER_NAME);
        $tracker          = (new XMLTracker("1", $tracker_itemname))->withName(self::MONO_TRACKER_NAME);

        $tracker_xml = $this->issues_from_project_in_mono_tracker_in_xml_exporter->exportIssuesToXml(
            $jira_platform_configuration,
            $tracker,
            $jira_base_url,
            $jira_project_key,
            $jira_issue_types,
            $field_id_generator,
            $linked_issues_collection,
            $import_mode,
        );

        JiraXMLNodeBuilder::appendTrackerXML($trackers_xml, $tracker_xml);
    }
}
