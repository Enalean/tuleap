<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\REST;

use Luracast\Restler\Restler;
use Tuleap\ProgramManagement\REST\v1\IterationResource;
use Tuleap\ProgramManagement\REST\v1\ProgramBacklogItemsResource;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\ProgramManagement\REST\v1\ProgramIncrementResource;
use Tuleap\ProgramManagement\REST\v1\ProjectResource;

final class ResourcesInjector
{
    public function populate(Restler $restler): void
    {
        $restler->addAPIClass(ProjectResource::class, ProjectRepresentation::ROUTE);
        $restler->addAPIClass(ProgramIncrementResource::class, ProgramIncrementResource::ROUTE);
        $restler->addAPIClass(ProgramBacklogItemsResource::class, ProgramBacklogItemsResource::ROUTE);
        $restler->addAPIClass(IterationResource::class, IterationResource::ROUTE);
    }
}
