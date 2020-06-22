<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

/*
 * This presenter represents an array of milestones with a common tracker (or milestone type).
 * The aim of this presenter is to be used in conjunction with a view that enables quick access
 * to each milestone.
 */
class Planning_Presenter_MilestoneAccessPresenter
{

    /**
     * @var Planning_Milestone[]
     */
    public $milestones;

    /**
     * @var string
     */
    public $milestone_type;

    public function __construct($milestones, $milestone_type)
    {
        $this->milestones     = $milestones;
        $this->milestone_type = $milestone_type;
    }

    public function content()
    {
        return dgettext('tuleap-agiledashboard', 'Overview');
    }

    public function planning()
    {
        return dgettext('tuleap-agiledashboard', 'Planning');
    }

    public function no_milestone()
    {
        return dgettext('tuleap-agiledashboard', 'There are no milestones for this period');
    }

    public function edit_base_link()
    {
        return '/plugins/tracker/?aid=';
    }
}
