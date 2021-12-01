<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML;

use Project;
use Tuleap\MailingList\ServiceMailingList;

/**
 * @psalm-immutable
 */
final class ListInfoFromVariables
{
    /**
     * @var int
     */
    private $list_id;
    /**
     * @var string
     */
    private $list_name;
    /**
     * @var array{group_id: int, list_name: string, is_public: int, description: string, group_list_id: int}
     */
    private $list_row;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var ServiceMailingList
     */
    private $service;

    /**
     * @psalm-param array{group_id: int, list_name: string, is_public: int, description: string, group_list_id: int} $list_row
     */
    public function __construct(
        int $list_id,
        string $list_name,
        array $list_row,
        Project $project,
        ServiceMailingList $service,
    ) {
        $this->list_id   = $list_id;
        $this->list_name = $list_name;
        $this->list_row  = $list_row;
        $this->project   = $project;
        $this->service   = $service;
    }

    public function getListId(): int
    {
        return $this->list_id;
    }

    public function getListName(): string
    {
        return $this->list_name;
    }

    /**
     * @return array{group_id: int, list_name: string, is_public: int, description: string, group_list_id: int}
     */
    public function getListRow(): array
    {
        return $this->list_row;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getService(): ServiceMailingList
    {
        return $this->service;
    }
}
