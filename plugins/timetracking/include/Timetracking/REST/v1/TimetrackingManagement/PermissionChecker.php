<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class PermissionChecker implements CheckPermission
{
    public function __construct(private GetWidgetInformation $dao)
    {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function checkThatCurrentUserCanUpdateTheQuery(int $query_id, \PFUser $current_user): Ok|Err
    {
        $widget_information = $this->dao->getWidgetInformationFromQuery($query_id);
        if ($widget_information !== null && $widget_information['user_id'] === $current_user->getId()) {
            return Result::ok(true);
        }

        return Result::err(WidgetNotFoundFault::build());
    }
}
