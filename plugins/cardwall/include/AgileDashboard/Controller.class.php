<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Cardwall_AgileDashboard_Controller {
    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    public function __construct(Codendi_Request $request, Planning_MilestoneFactory $milestone_factory) {
        $this->request = $request;
        $this->milestone_factory = $milestone_factory;
    }
}

?>
