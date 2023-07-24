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

use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

interface JiraAllIssuesInXmlExporter
{
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
    ): void;
}
