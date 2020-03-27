export default ProjectService;

ProjectService.$inject = ["Restangular"];

function ProjectService(Restangular) {
    return {
        reorderBacklog: reorderBacklog,
        getProject: getProject,
        getProjectBacklog: getProjectBacklog,
        removeAddReorderToBacklog: removeAddReorderToBacklog,
        removeAddToBacklog: removeAddToBacklog,
    };

    function reorderBacklog(project_id, dropped_item_ids, compared_to) {
        return getRest("v1")
            .one("projects", project_id)
            .all("backlog")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
            });
    }

    function removeAddReorderToBacklog(milestone_id, project_id, dropped_item_ids, compared_to) {
        return getRest("v1")
            .one("projects", project_id)
            .all("backlog")
            .patch({
                order: {
                    ids: dropped_item_ids,
                    direction: compared_to.direction,
                    compared_to: compared_to.item_id,
                },
                add: dropped_item_ids.map((dropped_item_id) => ({
                    id: dropped_item_id,
                    remove_from: milestone_id,
                })),
            });
    }

    function removeAddToBacklog(milestone_id, project_id, dropped_item_ids) {
        return getRest("v1")
            .one("projects", project_id)
            .all("backlog")
            .patch({
                add: dropped_item_ids.map((dropped_item_id) => ({
                    id: dropped_item_id,
                    remove_from: milestone_id,
                })),
            });
    }

    function getProject(project_id) {
        return getRest("v1").one("projects", project_id).get();
    }

    function getProjectBacklog(project_id) {
        /*
         * Use a string for the limit so that the server doesn't recognise a false value
         * and replace it with the default value. This means the response time is much faster.
         */
        var limit = "00";

        var promise = getRest("v2")
            .one("projects", project_id)
            .one("backlog")
            .get({
                limit: limit,
                offset: 0,
            })
            .then(function (response) {
                var result = {
                    allowed_backlog_item_types: getAllowedBacklogItemTypes(response.data),
                    has_user_priority_change_permission:
                        response.data.has_user_priority_change_permission,
                };

                return result;
            });

        return promise;
    }

    function getAllowedBacklogItemTypes(data) {
        const allowed_trackers = data.accept.trackers;
        const parent_trackers = data.accept.parent_trackers;

        const accepted_types = {
            content: allowed_trackers,
            parent_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };

        return accepted_types;
    }

    function getRest(version) {
        return Restangular.withConfig(function (RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl("/api/" + version);
        });
    }
}
