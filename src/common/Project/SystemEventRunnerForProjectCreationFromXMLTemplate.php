<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 */

namespace Tuleap\Project;

/**
 * In the case of project creation from an XML template, we shouldn't have to force the execution of
 * system events (to ensure actual stuff is created on filesystem) nor we should mandate to be root (root being needed
 * to import Data rather than structure)
 */
final class SystemEventRunnerForProjectCreationFromXMLTemplate implements SystemEventRunnerInterface
{
    public function checkPermissions(): void
    {
    }

    public function runSystemEvents(): void
    {
    }
}
