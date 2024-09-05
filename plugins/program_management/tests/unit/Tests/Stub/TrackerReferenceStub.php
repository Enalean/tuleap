<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\TrackerReference;

/**
 * @psalm-immutable
 */
final class TrackerReferenceStub implements TrackerReference
{
    private function __construct(private int $id, private string $name, private int $project_id, private string $project_name)
    {
    }

    public static function fromTracker(\Tracker $tracker): self
    {
        return new self($tracker->getId(), $tracker->getName(), (int) $tracker->getGroupId(), 'A project');
    }

    public static function withDefaults(): self
    {
        return self::withId(1);
    }

    public static function withId(int $id): self
    {
        return self::withIdAndLabel($id, 'tracker');
    }

    public static function withIdAndLabel(int $id, string $label): self
    {
        return self::withValues($id, $label, 101, 'A project');
    }

    public static function withProjectId(int $project_id): self
    {
        return self::withValues(1, 'tracker', $project_id, 'A project');
    }

    public static function withValues(int $id, string $name, int $project_id, string $project_name): self
    {
        return new self($id, $name, $project_id, $project_name);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getProjectLabel(): string
    {
        return $this->project_name;
    }
}
