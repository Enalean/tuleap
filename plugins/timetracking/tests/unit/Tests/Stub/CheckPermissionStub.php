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

namespace Tuleap\Timetracking\Tests\Stub;

use Tuleap\Timetracking\REST\v1\TimetrackingManagement\CheckPermission;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Result;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\WidgetNotFoundFault;

final readonly class CheckPermissionStub implements CheckPermission
{
    public function __construct(private bool $can_update)
    {
    }

    public static function withPermission(): self
    {
        return new self(true);
    }

    public static function withoutPermission(): self
    {
        return new self(false);
    }

    public function checkThatCurrentUserCanUpdateTheQuery(int $query_id, \PFUser $current_user): Ok|Err
    {
        return $this->can_update ? Result::ok(true) : Result::err(WidgetNotFoundFault::build());
    }
}
