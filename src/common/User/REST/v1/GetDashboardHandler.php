<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\User\REST\v1;

use Tuleap\Dashboard\User\UserDashboard;
use Tuleap\Dashboard\User\UserDashboardRetriever;

final readonly class GetDashboardHandler
{
    public function __construct(private UserDashboardRetriever $dashboard_retriever)
    {
    }

    public function handle(\PFUser $user, int $limit, int $offset): PaginatedCollectionOfDashboardRepresentation
    {
        $dashboards = $this->dashboard_retriever->getAllUserDashboards($user);

        return new PaginatedCollectionOfDashboardRepresentation(
            array_values(
                array_map(
                    static fn (UserDashboard $dashboard) => DashboardRepresentation::fromDashboard($dashboard),
                    array_slice($dashboards, $offset, $limit),
                ),
            ),
            count($dashboards),
        );
    }
}
