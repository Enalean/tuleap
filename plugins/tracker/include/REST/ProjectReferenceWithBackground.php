<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST;

use Project;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Project\REST\v1\HeaderBackgroundRepresentation;

/**
 * @psalm-immutable
 */
final class ProjectReferenceWithBackground extends ProjectReference
{
    /**
     * @var HeaderBackgroundRepresentation | null
     */
    public $background;

    private function __construct(Project $project, ?HeaderBackgroundRepresentation $background)
    {
        parent::__construct($project);
        $this->background = $background;
    }

    public static function fromProject(Project $project, ProjectBackgroundConfiguration $project_background_configuration): self
    {
        $background_identifier = $project_background_configuration->getBackground($project);
        return new self(
            $project,
            $background_identifier === null ? null : HeaderBackgroundRepresentation::fromBackgroundName($background_identifier)
        );
    }
}
