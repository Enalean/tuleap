<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Cardwall_UserPreferences_Autostack_AutostackDashboard extends Cardwall_UserPreferences_UserPreferencesAutostack
{
    /**
     * @param Tracker $tracker
     */
    private $tracker;

    public function __construct(PFUser $user, Tracker $tracker)
    {
        parent::__construct($user);
        $this->tracker = $tracker;
    }

    public function getName(Cardwall_Column $column)
    {
        return 'cardwall_dashboard_' . $this->tracker->getId() . '_column_' . $column->getId();
    }
}
