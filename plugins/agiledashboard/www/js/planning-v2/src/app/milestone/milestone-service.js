(function () {
    angular
        .module('milestone')
        .service('MilestoneService', MilestoneService);

    MilestoneService.$inject = ['Restangular', '$q', 'BacklogItemFactory'];

    function MilestoneService(Restangular, $q, BacklogItemFactory) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            milestone_content_pagination : { limit: 50, offset: 0 },

            getMilestone                 : getMilestone,
            getOpenMilestones            : getOpenMilestones,
            getOpenSubMilestones         : getOpenSubMilestones,
            getClosedMilestones          : getClosedMilestones,
            getClosedSubMilestones       : getClosedSubMilestones,
            putSubMilestones             : putSubMilestones,
            getContent                   : getContent,
            reorderBacklog               : reorderBacklog,
            removeAddReorderToBacklog    : removeAddReorderToBacklog,
            removeAddToBacklog           : removeAddToBacklog,
            reorderContent               : reorderContent,
            addReorderToContent          : addReorderToContent,
            addToContent                 : addToContent,
            removeAddReorderToContent    : removeAddReorderToContent,
            removeAddToContent           : removeAddToContent,
            updateInitialEffort          : updateInitialEffort,
            defineAllowedBacklogItemTypes: defineAllowedBacklogItemTypes,
            augmentMilestone             : augmentMilestone
        };

        function getMilestone(milestone_id, limit, offset, scope_items) {
            var data = $q.defer();

            rest.one('milestones', milestone_id)
                .get()
                .then(function(response) {
                    defineAllowedBacklogItemTypes(response.data);
                    augmentMilestone(response.data, limit, offset, scope_items);

                    result = {
                        results: response.data
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getOpenMilestones(project_id, limit, offset, scope_items) {
            return getMilestones('projects', project_id, limit, offset, 'desc', 'open', scope_items);
        }

        function getOpenSubMilestones(milestone_id, limit, offset, scope_items) {
            return getMilestones('milestones', milestone_id, limit, offset, 'desc', 'open', scope_items);
        }

        function getClosedMilestones(project_id, limit, offset, scope_items) {
            return getMilestones('projects', project_id, limit, offset, 'desc', 'closed', scope_items);
        }

        function getClosedSubMilestones(milestone_id, limit, offset, scope_items) {
            return getMilestones('milestones', milestone_id, limit, offset, 'desc', 'closed', scope_items);
        }

        function getMilestones(parent_type, parent_id, limit, offset, order, status, scope_items) {
            var data = $q.defer();

            rest.one(parent_type, parent_id)
                .all('milestones')
                .getList({
                    limit : limit,
                    offset: offset,
                    order : order,
                    query : {
                        status: status
                    },
                    fields: 'slim'
                })
                .then(function(response) {
                    _.forEach(response.data, function(milestone) {
                        augmentMilestone(milestone, limit, offset, scope_items);
                    });

                    result = {
                        results: response.data,
                        total  : response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function putSubMilestones(milestone_id, submilestone_ids) {
            return rest.one('milestones', milestone_id)
               .customPUT(
                   {
                       id : milestone_id,
                       ids: submilestone_ids
                   },
                   'milestones'
                );
        }

        function getContent(milestone_id, limit, offset) {
            var data = $q.defer();

            rest.one('milestones', milestone_id)
                .all('content')
                .getList({
                    limit: limit,
                    offset: offset
                })
                .then(function(response) {
                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function augmentMilestone(milestone, limit, offset, scope_items) {
            addContentDataToMilestone(milestone);
            setMilestoneCollapsedByDefault(milestone);
            defineAllowedContentItemTypes(milestone);

            function setMilestoneCollapsedByDefault(milestone) {
                milestone.collapsed = true;
            }

            function addContentDataToMilestone(milestone) {
                milestone.content       = [];
                milestone.initialEffort = 0;
                milestone.getContent    = function() {
                    milestone.loadingContent = true;
                    milestone.alreadyLoaded  = true;

                    return fetchMilestoneContent(limit, offset);
                };

                function fetchMilestoneContent(limit, offset) {
                    return getContent(milestone.id, limit, offset).then(function(data) {
                        angular.forEach(data.results, function(backlog_item, key) {
                            scope_items[backlog_item.id] = backlog_item;
                            augmentBacklogItem(backlog_item);

                            milestone.content.push(scope_items[backlog_item.id]);
                        });

                        updateInitialEffort(milestone);

                        if ((limit + offset) < data.total) {
                            return fetchMilestoneContent(limit, offset + limit);
                        } else {
                            milestone.loadingContent = false;
                            return;
                        }
                    });
                }

                function augmentBacklogItem(data) {
                    BacklogItemFactory.augment(data);
                }
            }
        }

        function updateInitialEffort(milestone) {
            var initial_effort = 0;

            _.forEach(milestone.content, function(backlog_item) {
                initial_effort += backlog_item.initial_effort;
            });

            milestone.initialEffort = initial_effort;
        }

        function defineAllowedBacklogItemTypes(milestone) {
            var allowed_trackers = milestone.resources.backlog.accept.trackers;

            milestone.backlog_accepted_types = {
                content : allowed_trackers,
                toString: function() {
                    var accept = [];
                    _.forEach(this.content, function(allowed_tracker) {
                        accept.push('trackerId' + allowed_tracker.id);
                    });

                    return accept.join('|');
                }
            };
        }

        function defineAllowedContentItemTypes(milestone) {
            var allowed_trackers = milestone.resources.content.accept.trackers;

            milestone.content_accepted_types = {
                content : allowed_trackers,
                toString: function() {
                    var accept = [];
                    _.forEach(this.content, function(allowed_tracker) {
                        accept.push('trackerId' + allowed_tracker.id);
                    });

                    return accept.join('|');
                }
            };
        }

        function reorderBacklog(milestone_id, dropped_item_ids, compared_to) {
            return rest.one('milestones', milestone_id)
                .all('backlog')
                .patch({
                    order: {
                        ids         : dropped_item_ids,
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    }
                });
        }

        function removeAddReorderToBacklog(source_milestone_id, dest_milestone_id, dropped_item_ids, compared_to) {
            return rest.one('milestones', dest_milestone_id)
                .all('backlog')
                .patch({
                    order: {
                        ids         : dropped_item_ids,
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    },
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return {
                            id         : dropped_item_id,
                            remove_from: source_milestone_id
                        };
                    })
                });
        }

        function removeAddToBacklog(source_milestone_id, dest_milestone_id, dropped_item_ids) {
            return rest.one('milestones', dest_milestone_id)
                .all('backlog')
                .patch({
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return {
                            id         : dropped_item_id,
                            remove_from: source_milestone_id
                        };
                    })
                });
        }

        function reorderContent(milestone_id, dropped_item_ids, compared_to) {
            return rest.one('milestones', milestone_id)
                .all('content')
                .patch({
                    order: {
                        ids         : dropped_item_ids,
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    }
                });
        }

        function addReorderToContent(milestone_id, dropped_item_ids, compared_to) {
            return rest.one('milestones', milestone_id)
                .all('content')
                .patch({
                    order: {
                        ids         : dropped_item_ids,
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    },
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return { id: dropped_item_id };
                    })
                });
        }

        function addToContent(milestone_id, dropped_item_ids) {
            return rest.one('milestones', milestone_id)
                .all('content')
                .patch({
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return { id: dropped_item_id };
                    })
                });
        }

        function removeAddReorderToContent(source_milestone_id, dest_milestone_id, dropped_item_ids, compared_to) {
            return rest.one('milestones', dest_milestone_id)
                .all('content')
                .patch({
                    order: {
                        ids         : dropped_item_ids,
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    },
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return {
                            id         : dropped_item_id,
                            remove_from: source_milestone_id
                        };
                    })
                });
        }

        function removeAddToContent(source_milestone_id, dest_milestone_id, dropped_item_ids) {
            return rest.one('milestones', dest_milestone_id)
                .all('content')
                .patch({
                    add: _.map(dropped_item_ids, function(dropped_item_id) {
                        return {
                            id         : dropped_item_id,
                            remove_from: source_milestone_id
                        };
                    })
                });
        }
    }
})();
