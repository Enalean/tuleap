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
use Tuleap\JiraImport\Project\CreateProjectFromJira;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

final class JiraAllIssuesInXmlExporterBuilder
{
    public static function build(
        JiraClient $jira_client,
        LoggerInterface $logger,
        JiraUserOnTuleapCache $jira_user_on_tuleap_cache,
    ): JiraAllIssuesInXmlExporter {
        if (\ForgeConfig::getFeatureFlag(CreateProjectFromJira::FLAG_JIRA_IMPORT_MONO_TRACKER_MODE)) {
            return JiraAllIssuesMonoTrackersInXmlExporter::build(
                $jira_client,
                $logger,
            );
        }

        return JiraAllIssuesMultiTrackersInXmlExporter::build(
            $jira_client,
            $logger,
            $jira_user_on_tuleap_cache,
        );
    }
}
