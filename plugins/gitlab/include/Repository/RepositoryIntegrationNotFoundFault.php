<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use Project;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\NeverThrow\Fault;

/**
 * @psalm-immutable
 */
final readonly class RepositoryIntegrationNotFoundFault extends Fault
{
    public static function build(Project $project, GitlabProject $gitlab_project): Fault
    {
        return new self(sprintf('Gitlab integration #%d not found in the project #%d', $gitlab_project->getId(), (int) $project->getID()));
    }
}
