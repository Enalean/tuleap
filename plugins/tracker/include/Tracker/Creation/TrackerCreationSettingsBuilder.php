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

namespace Tuleap\Tracker\Creation;

use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownDao;

class TrackerCreationSettingsBuilder
{
    /**
     * @var TrackerInNewDropdownDao
     */
    private $in_new_dropdown_dao;

    public function __construct(TrackerInNewDropdownDao $in_new_dropdown_dao)
    {
        $this->in_new_dropdown_dao = $in_new_dropdown_dao;
    }

    public function build(\Tracker $tracker): TrackerCreationSettings
    {
        $is_displayed_in_new_dropdown = false;
        if ($this->in_new_dropdown_dao->isContaining($tracker->getId())) {
            $is_displayed_in_new_dropdown = true;
        }

        return new TrackerCreationSettings($is_displayed_in_new_dropdown);
    }
}
