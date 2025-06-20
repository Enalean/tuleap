<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Tracker;

use Planning;
use Tuleap\Tracker\Tracker;

final class TrackersCannotBeLinkedWithHierarchyException extends \Exception
{
    public function __construct(Planning $planning, Tracker $parent_tracker, Tracker $child_tracker)
    {
        parent::__construct(
            sprintf(
                dgettext('tuleap-agiledashboard', 'Tracker %s cannot be defined as child of tracker %s because they are part of the same backlog (%s)'),
                $child_tracker->getName(),
                $parent_tracker->getName(),
                $planning->getName(),
            )
        );
    }
}
