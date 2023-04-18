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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;

final class FieldSkippedInSnapshotChecker
{
    public static function mustFieldBeSkippedById(string $field_id): bool
    {
        return $field_id === JiraToTuleapFieldTypeMapper::JIRA_FIELD_VERSIONS ||
            $field_id === JiraToTuleapFieldTypeMapper::JIRA_FIELD_FIXEDVERSIONS ||
            $field_id === JiraToTuleapFieldTypeMapper::JIRA_FIELD_COMPONENTS;
    }

    public static function mustFieldBeSkippedByJiraSchema(string $jira_field_schema): bool
    {
        return $jira_field_schema === JiraToTuleapFieldTypeMapper::JIRA_FIELD_CUSTOM_MULTIVERSION ||
            $jira_field_schema === JiraToTuleapFieldTypeMapper::JIRA_FIELD_CUSTOM_VERSION ||
            $jira_field_schema === JiraToTuleapFieldTypeMapper::JIRA_FIELD_CUSTOM_MULTICHECKBOXES;
    }
}
