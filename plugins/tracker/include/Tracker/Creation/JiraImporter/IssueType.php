<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

class IssueType
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $is_subtask;

    public function __construct(string $id, string $name, bool $is_subtask)
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->is_subtask = $is_subtask;
    }

    public static function buildFromAPIResponse(array $json_issue_type): self
    {
        if (! isset($json_issue_type['id']) || ! isset($json_issue_type['name']) || ! isset($json_issue_type['subtask'])) {
            throw new \LogicException('IssueType does not have an id or a name or the subtask information.');
        }

        return new self(
            (string) $json_issue_type['id'],
            (string) $json_issue_type['name'],
            $json_issue_type['subtask'],
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSubtask(): bool
    {
        return $this->is_subtask;
    }

    public function toArray(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
