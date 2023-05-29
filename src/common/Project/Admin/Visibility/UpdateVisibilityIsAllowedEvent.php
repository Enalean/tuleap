<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Visibility;

use Project;
use Tuleap\Event\Dispatchable;

final class UpdateVisibilityIsAllowedEvent implements Dispatchable
{
    private UpdateVisibilityStatus $update_visibility_status;

    public function __construct(private readonly Project $project)
    {
        $this->update_visibility_status = UpdateVisibilityStatus::buildStatusSwitchIsAllowed();
    }

    public function getUpdateVisibilityStatus(): UpdateVisibilityStatus
    {
        return $this->update_visibility_status;
    }

    public function setUpdateVisibilityStatus(UpdateVisibilityStatus $update_visibility_status): void
    {
        $this->update_visibility_status = $update_visibility_status;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
