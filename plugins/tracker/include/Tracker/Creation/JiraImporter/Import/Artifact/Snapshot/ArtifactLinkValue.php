<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 * @psalm-type LinkType = array{id: string, name: string, inward: string, outward: string, self: string}
 * @psalm-type LinkedIssueFields = array{status: array, priority: array, issuetype: array}
 * @psalm-type LinkedIssue = array{id: string, key: string, self: string, fields: LinkedIssueFields}
 * @psalm-type SubTasksFields = array{summary: string, status: array, priority: array, issuetype: array}
 */
final class ArtifactLinkValue
{
    /**
     * @var list<array{id: string, type: LinkType, inwardIssue: LinkedIssue, outwardIssue: LinkedIssue}>
     */
    public $issuelinks;
    /**
     * @var list<array{id: string, key: string, self: string, fields: SubTasksFields}>
     */
    public $subtasks;

    public function __construct(array $issuelinks, array $subtasks)
    {
        $this->issuelinks = $issuelinks;
        $this->subtasks   = $subtasks;
    }
}
