<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\WorkFlowError;

/**
 * @psalm-immutable
 */
final class WorkFlowErrorPresenter
{
    public int $tracker_id;
    public string $tracker_label;
    public string $project_label;
    public string $tracker_url;

    public function __construct(WorkFlowError $workflow_error)
    {
        $this->tracker_id    = $workflow_error->tracker_id;
        $this->tracker_label = $workflow_error->tracker_label;
        $this->project_label = $workflow_error->project_label;
        $this->tracker_url   = $workflow_error->tracker_url;
    }
}
