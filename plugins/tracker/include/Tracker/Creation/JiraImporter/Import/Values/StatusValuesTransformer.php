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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Values;

class StatusValuesTransformer
{
    private const START_OF_STATUS_INDEX = 9000000;

    /**
     * Jira statuses IDs and Jira list field values IDs are not unique.
     * These are 2 different concepts in Jira that will be both transformed as a static list value.
     * To ensure that IDs will be unique in XML, we will transform the status IDs by adding a large int to it.
     */
    public function transformJiraStatusValue(int $base_jira_status_id): int
    {
        return self::START_OF_STATUS_INDEX + $base_jira_status_id;
    }
}
