<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST;

use Luracast\Restler\Restler;
use Tuleap\Kanban\REST\v1\KanbanColumnRepresentation;
use Tuleap\Kanban\REST\v1\KanbanItemPOSTRepresentation;
use Tuleap\Kanban\REST\v1\KanbanRepresentation;

/**
 * Inject resource into restler
 */
final class ResourcesInjector
{
    public function populate(Restler $restler): void
    {
        $restler->addAPIClass('\\Tuleap\\Kanban\\REST\\v1\\KanbanResource', KanbanRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Kanban\\REST\\v1\\KanbanColumnsResource', KanbanColumnRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\Kanban\\REST\\v1\\KanbanItemsResource', KanbanItemPOSTRepresentation::ROUTE);
    }
}
