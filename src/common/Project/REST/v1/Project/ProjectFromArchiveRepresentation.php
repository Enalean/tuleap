<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1\Project;

use Project;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\REST\JsonCast;

/**
* @psalm-immutable
 */
final readonly class ProjectFromArchiveRepresentation
{
    private function __construct(public int $id, public string $uri, public string $upload_href)
    {
    }

    public static function fromCreatedProject(Project $project, string $upload_href): self
    {
        return new self(
            JsonCast::toInt($project->getID()),
            ProjectRepresentation::ROUTE . '/' . $project->getID(),
            $upload_href
        );
    }
}
