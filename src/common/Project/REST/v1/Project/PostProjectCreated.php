<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1\Project;

use Project;
use Tuleap\Project\REST\ProjectRepresentation;

final readonly class PostProjectCreated
{
    public function __construct(private ProjectRepresentationBuilder $builder, private \PFUser $user, private ?Project $project, private ?CreatedFileRepresentation $created_file_representation)
    {
    }

    public static function fromProject(ProjectRepresentationBuilder $builder, \PFUser $user, Project $project): self
    {
        return new self($builder, $user, $project, null);
    }

    public static function fromArchive(ProjectRepresentationBuilder $builder, \PFUser $user, CreatedFileRepresentation $created_file_representation): self
    {
        return new self($builder, $user, null, $created_file_representation);
    }

    public function getProjectRepresentation(): ProjectRepresentation
    {
        if ($this->project === null) {
            throw new \LogicException("Can not renderer ProjectRepresentation when project is not defined");
        }
        return $this->builder->build($this->project, $this->user);
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function getFileRepresentation(): ?CreatedFileRepresentation
    {
        return $this->created_file_representation;
    }
}
