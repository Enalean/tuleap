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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content;

/**
 * @psalm-immutable
 */
final class FeatureChange
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $project_id;

    private function __construct(int $id, int $project_id)
    {
        $this->id         = $id;
        $this->project_id = $project_id;
    }

    /**
     * @psalm-param array{id: int, project_id: int} $link
     */
    public static function fromRaw(array $link): self
    {
        return new self($link['id'], $link['project_id']);
    }
}
