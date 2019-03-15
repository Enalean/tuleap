<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline;

use Project;

class BaselineArtifact
{
    /** @var int */
    private $id;

    /** @var ?string */
    private $title;

    /** @var ?string */
    private $description;

    /** @var ?int */
    private $initial_effort;

    /** @var ?string */
    private $status;

    /** @var Project */
    private $project;

    public function __construct(int $id, $title, $description, $initial_effort, $status, Project $project)
    {
        $this->id             = $id;
        $this->title          = $title;
        $this->description    = $description;
        $this->initial_effort = $initial_effort;
        $this->status         = $status;
        $this->project        = $project;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getInitialEffort(): ?int
    {
        return $this->initial_effort;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
