<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Tuleap\DB\DataAccessObject;

/**
 * Manage ID sharing between the old tracker engine (aka tracker v3) and the current one.
 *
 * Tracker v3 is now removed, but for the sake of simplicity, we keep the id sharing mechanism.
 * There is no urgency of removing this, it can be removed later.
 */
class TrackerIdSharingDao extends DataAccessObject
{
    public function generateTrackerId(): int
    {
        return (int) $this->getDB()->insertReturnId('tracker_idsharing_tracker', []);
    }

    public function generateArtifactId(): int
    {
        return (int) $this->getDB()->insertReturnId('tracker_idsharing_artifact', []);
    }
}
