/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

export default MilestoneCollectionService;

MilestoneCollectionService.$inject = ["MilestoneService", "BacklogItemCollectionService"];

function MilestoneCollectionService(MilestoneService, BacklogItemCollectionService) {
    const self = this;
    Object.assign(self, {
        milestones: {
            content: [],
            loading: false,
            open_milestones_fully_loaded: false,
            closed_milestones_fully_loaded: false,
        },
        getMilestone: getMilestone,
        refreshMilestone: refreshMilestone,
        removeBacklogItemsFromMilestoneContent: removeBacklogItemsFromMilestoneContent,
        addOrReorderBacklogItemsInMilestoneContent: addOrReorderBacklogItemsInMilestoneContent,
    });

    function getMilestone(milestone_id) {
        return self.milestones.content.find(({ id }) => id === milestone_id);
    }

    function refreshMilestone(milestone_id) {
        var milestone = getMilestone(milestone_id);

        MilestoneService.updateInitialEffort(milestone);
    }

    function removeBacklogItemsFromMilestoneContent(milestone_id, backlog_items) {
        var milestone = getMilestone(milestone_id);

        BacklogItemCollectionService.removeBacklogItemsFromCollection(
            milestone.content,
            backlog_items,
        );
    }

    function addOrReorderBacklogItemsInMilestoneContent(milestone_id, backlog_items, compared_to) {
        var milestone = getMilestone(milestone_id);

        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            milestone.content,
            backlog_items,
            compared_to,
        );
    }
}
