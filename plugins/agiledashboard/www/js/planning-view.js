/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

document.observe('dom:loaded', function () {
    var planner = $('planner'),
        milestone_planning = $('planning'),
        top_milestone_planning = $('topplanning'),
        milestone_content = $('blcontent'),
        top_milestone_content = $('topblcontent'),
        params = {};

    if (planner) {
        new tuleap.agiledashboard.Planning(planner);
    }

    if (milestone_content) {
        tuleap.agiledashboard.MilestoneContent(milestone_content);
    }

    if (top_milestone_content) {
        tuleap.agiledashboard.MilestoneContent(top_milestone_content);
    }

    if (milestone_planning) {
        params.data_route = 'submilestonedata';
        params.is_top = false;

        new tuleap.agiledashboard.NewPlanning(params);
    }

    if (top_milestone_planning) {
        params.data_route = 'milestonedata';
        params.is_top = true;
        
        new tuleap.agiledashboard.NewPlanning(params);
    }

    tuleap.agiledashboard.align_short_access_heights.defer();
});
