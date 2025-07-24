<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\ProjectReference;

/**
 * @psalm-immutable
 */
final class ProjectReferenceStub implements ProjectReference
{
    private function __construct(private int $id, private string $label, private string $short_name, private string $project_icon)
    {
    }

    public static function buildGeneric(): self
    {
        return new self(101, 'My project', 'my_project', '');
    }

    public static function withId(int $project_id): self
    {
        return new self($project_id, 'My project', 'my_project', '');
    }

    public static function withValues(int $project_id, string $label, string $short_name, string $project_icon): self
    {
        return new self($project_id, $label, $short_name, $project_icon);
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[\Override]
    public function getProjectLabel(): string
    {
        return $this->label;
    }

    #[\Override]
    public function getUrl(): string
    {
        return '/projects/' . urlencode($this->short_name);
    }

    #[\Override]
    public function getProjectIcon(): string
    {
        return $this->project_icon;
    }

    #[\Override]
    public function getProjectShortName(): string
    {
        return $this->short_name;
    }
}
