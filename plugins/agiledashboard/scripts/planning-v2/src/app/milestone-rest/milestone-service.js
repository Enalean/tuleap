export default MilestoneService;

MilestoneService.$inject = ["Restangular", "BacklogItemFactory"];

function MilestoneService(Restangular, BacklogItemFactory) {
    var self = this,
        rest = Restangular.withConfig(function (RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl("/api/v1");
        });

    Object.assign(self, {
        milestone_content_pagination: { limit: 50, offset: 0 },
        getMilestone: getMilestone,
        getOpenMilestones: getOpenMilestones,
        getOpenSubMilestones: getOpenSubMilestones,
        getClosedMilestones: getClosedMilestones,
        getClosedSubMilestones: getClosedSubMilestones,
        putSubMilestones: putSubMilestones,
        patchSubMilestones: patchSubMilestones,
        getContent: getContent,
        reorderBacklog: reorderBacklog,
        removeAddReorderToBacklog: removeAddReorderToBacklog,
        removeAddToBacklog: removeAddToBacklog,
        reorderContent: reorderContent,
        addReorderToContent: addReorderToContent,
        addToContent: addToContent,
        removeAddReorderToContent: removeAddReorderToContent,
        removeAddToContent: removeAddToContent,
        updateInitialEffort: updateInitialEffort,
        defineAllowedBacklogItemTypes: defineAllowedBacklogItemTypes,
        augmentMilestone: augmentMilestone,
    });

    function getMilestone(milestone_id, scope_items) {
        var promise = rest
            .one("milestones", milestone_id)
            .get()
            .then(function (response) {
                defineAllowedBacklogItemTypes(response.data);
                augmentMilestone(response.data, scope_items);

                var result = {
                    results: response.data,
                };

                return result;
            });

        return promise;
    }

    function getOpenMilestones(project_id, limit, offset, scope_items) {
        return getMilestones("projects", project_id, limit, offset, "desc", "open", scope_items);
    }

    function getOpenSubMilestones(milestone_id, limit, offset, scope_items) {
        return getMilestones(
            "milestones",
            milestone_id,
            limit,
            offset,
            "desc",
            "open",
            scope_items
        );
    }

    function getClosedMilestones(project_id, limit, offset, scope_items) {
        return getMilestones("projects", project_id, limit, offset, "desc", "closed", scope_items);
    }

    function getClosedSubMilestones(milestone_id, limit, offset, scope_items) {
        return getMilestones(
            "milestones",
            milestone_id,
            limit,
            offset,
            "desc",
            "closed",
            scope_items
        );
    }

    function getMilestones(parent_type, parent_id, limit, offset, order, status, scope_items) {
        var promise = rest
            .one(parent_type, parent_id)
            .all("milestones")
            .getList({
                limit: limit,
                offset: offset,
                order: order,
                query: {
                    status: status,
                },
                fields: "slim",
            })
            .then(function (response) {
                response.data.forEach((milestone) => augmentMilestone(milestone, scope_items));

                var result = {
                    results: response.data,
                    total: response.headers("X-PAGINATION-SIZE"),
                };

                return result;
            });

        return promise;
    }

    function putSubMilestones(milestone_id, submilestone_ids) {
        return rest.one("milestones", milestone_id).customPUT(
            {
                id: milestone_id,
                ids: submilestone_ids,
            },
            "milestones"
        );
    }

    function patchSubMilestones(milestone_id, submilestone_ids) {
        return rest
            .one("milestones", milestone_id)
            .all("milestones")
            .patch({
                add: submilestone_ids.map((id) => ({ id })),
            });
    }

    function getContent(milestone_id, limit, offset) {
        var promise = rest
            .one("milestones", milestone_id)
            .all("content")
            .getList({
                limit: limit,
                offset: offset,
            })
            .then(function (response) {
                var result = {
                    results: response.data,
                    total: response.headers("X-PAGINATION-SIZE"),
                };

                return result;
            });

        return promise;
    }

    function augmentMilestone(milestone, scope_items) {
        addContentDataToMilestone(milestone);
        setMilestoneCollapsedByDefault(milestone);
        defineAllowedContentItemTypes(milestone);

        function setMilestoneCollapsedByDefault(milestone) {
            milestone.collapsed = true;
        }

        function addContentDataToMilestone(milestone) {
            milestone.content = [];
            milestone.initialEffort = 0;
            milestone.getContent = function () {
                milestone.loadingContent = true;
                milestone.alreadyLoaded = true;

                return fetchMilestoneContent(
                    self.milestone_content_pagination.limit,
                    self.milestone_content_pagination.offset
                );
            };

            function fetchMilestoneContent(limit, offset) {
                return getContent(milestone.id, limit, offset).then((data) => {
                    data.results.forEach((backlog_item) => {
                        scope_items[backlog_item.id] = backlog_item;
                        augmentBacklogItem(backlog_item);

                        milestone.content.push(scope_items[backlog_item.id]);
                    });

                    updateInitialEffort(milestone);

                    if (limit + offset < data.total) {
                        return fetchMilestoneContent(limit, offset + limit);
                    }
                    milestone.loadingContent = false;
                });
            }

            function augmentBacklogItem(data) {
                BacklogItemFactory.augment(data);
            }
        }
    }

    function updateInitialEffort(milestone) {
        milestone.initialEffort = milestone.content.reduce(
            (previous_sum, backlog_item) => previous_sum + backlog_item.initial_effort,
            0
        );
    }

    function defineAllowedBacklogItemTypes(milestone) {
        const allowed_trackers = milestone.resources.backlog.accept.trackers;
        const parent_trackers = milestone.resources.backlog.accept.parent_trackers;

        milestone.backlog_accepted_types = {
            content: allowed_trackers,
            parent_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };
    }

    function defineAllowedContentItemTypes(milestone) {
        var allowed_trackers = milestone.resources.content.accept.trackers;

        milestone.content_accepted_types = {
            content: allowed_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };
    }

    function reorderBacklog(milestone_id, dropped_item_ids, compared_to) {
        return rest
            .one("milestones", milestone_id)
            .all("backlog")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
            });
    }

    function removeAddReorderToBacklog(
        source_milestone_id,
        dest_milestone_id,
        dropped_item_ids,
        compared_to
    ) {
        return rest
            .one("milestones", dest_milestone_id)
            .all("backlog")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
                add: dropped_item_ids.map((dropped_item_id) => ({
                    id: dropped_item_id,
                    remove_from: source_milestone_id,
                })),
            });
    }

    function removeAddToBacklog(source_milestone_id, dest_milestone_id, dropped_item_ids) {
        return rest
            .one("milestones", dest_milestone_id)
            .all("backlog")
            .patch({
                add: dropped_item_ids.map((dropped_item_id) => ({
                    id: dropped_item_id,
                    remove_from: source_milestone_id,
                })),
            });
    }

    function reorderContent(milestone_id, dropped_item_ids, compared_to) {
        return rest
            .one("milestones", milestone_id)
            .all("content")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
            });
    }

    function addReorderToContent(milestone_id, dropped_item_ids, compared_to) {
        return rest
            .one("milestones", milestone_id)
            .all("content")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
                add: dropped_item_ids.map((id) => ({ id })),
            });
    }

    function addToContent(milestone_id, dropped_item_ids) {
        return rest
            .one("milestones", milestone_id)
            .all("content")
            .patch({
                add: dropped_item_ids.map((id) => ({ id })),
            });
    }

    function removeAddReorderToContent(
        source_milestone_id,
        dest_milestone_id,
        dropped_item_ids,
        compared_to
    ) {
        return rest
            .one("milestones", dest_milestone_id)
            .all("content")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
                add: dropped_item_ids.map((dropped_item_id) => ({
                    id: dropped_item_id,
                    remove_from: source_milestone_id,
                })),
            });
    }

    function removeAddToContent(source_milestone_id, dest_milestone_id, dropped_item_ids) {
        return rest
            .one("milestones", dest_milestone_id)
            .all("content")
            .patch({
                add: dropped_item_ids.map((dropped_item_id) => ({
                    id: dropped_item_id,
                    remove_from: source_milestone_id,
                })),
            });
    }
}
