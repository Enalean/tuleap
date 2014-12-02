(function () {
    angular
        .module('milestone')
        .service('MilestoneService', MilestoneService);

    MilestoneService.$inject = ['Restangular', '$q'];

    function MilestoneService(Restangular, $q) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getSubMilestones: getSubMilestones,
            getMilestones   : getMilestones,
            getContent      : getContent
        };

        function getMilestones(project_id, limit, offset) {
            var data = $q.defer();

            rest.one('projects', project_id)
                .all('milestones')
                .getList({
                    limit: limit,
                    offset: offset,
                    order: 'desc'
                })
                .then(function(response) {
                    _.forEach(response.data, function(milestone) {
                        augmentMilestone(milestone, limit, offset);
                    });

                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getSubMilestones(milestone_id, limit, offset) {
            var data = $q.defer();

            rest.one('milestones', milestone_id)
                .all('milestones')
                .getList({
                    limit: limit,
                    offset: offset,
                    order: 'desc'
                })
                .then(function(response) {
                    _.forEach(response.data, function(milestone) {
                        augmentMilestone(milestone, limit, offset);
                    });

                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
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

        function augmentMilestone(milestone, limit, offset) {
            addContentDataToMilestone(milestone);
            defineCurrentToggleState(milestone);

            function defineCurrentToggleState(milestone) {
                if (milestone.semantic_status === 'closed') {
                    milestone.collapsed = true;
                } else {
                    milestone.getContent();
                }

                return milestone;
            }

            function addContentDataToMilestone(milestone) {
                milestone.content       = [];
                milestone.initialEffort = 0;
                milestone.getContent    = function() {
                    milestone.loadingContent = true;
                    milestone.alreadyLoaded  = true;

                    fetchMilestoneContent(milestone, limit, offset);
                };

                function fetchMilestoneContent(milestone, limit, offset) {
                    getContent(milestone.id, limit, offset).then(function(data) {
                        milestone.content = milestone.content.concat(data.results);

                        _.forEach(data.results, updateInitialEffort);
                        _.forEach(data.results, augmentBacklogItem);

                        if (milestone.content.length < data.total) {
                            fetchMilestoneContent(milestone, limit, offset + limit);
                        } else {
                            milestone.loadingContent = false;
                        }
                    });
                }

                function updateInitialEffort(backlog_item) {
                    milestone.initialEffort += backlog_item.initial_effort;
                }

                function augmentBacklogItem(backlog_item) {
                    backlog_item.children        = [];
                    backlog_item.children_loaded = false;

                    backlog_item.isOpen = function() {
                        return this.status === 'Open';
                    };
                }
            }
        }
    }
})();