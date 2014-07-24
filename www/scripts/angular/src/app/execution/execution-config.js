(function () {
    angular
        .module('execution')
        .config(ExecutionConfig);

    ExecutionConfig.$inject = ['$stateProvider'];

    function ExecutionConfig($stateProvider) {
        $stateProvider.state('campaigns.executions', {
            url:         '/{id:[0-9]+}-{slug}',
            controller:  'ExecutionListCtrl',
            templateUrl: 'execution/execution-list.tpl.html',
            resolve: {
                executionService: 'ExecutionService',
                campaignService:  'CampaignService',
                executions:       resolveExecutionsGroupedByCategory,
                assignees:        resolveAssignees,
                environments:     resolveEnvironments
            },
            data: {
                ncyBreadcrumbLabel:  '{{ campaign.label }}',
                ncyBreadcrumbParent: 'campaigns.list'
            }
        }).state('campaigns.executions.detail', {
            url:         '/{defid:[0-9]+}-{defslug}',
            controller:  'ExecutionDetailCtrl',
            templateUrl: 'execution/execution-detail.tpl.html',
            data: {
                ncyBreadcrumbLabel:  '{{ execution.definition.summary }}',
                ncyBreadcrumbParent: 'campaigns.executions'
            }
        });
    }

    function resolveExecutionsGroupedByCategory(executionService, $stateParams) {
        var nb_fetched  = 0,
            categories  = {},
            limit       = 50,
            offset      = 0,
            campaign_id = $stateParams.id;

        return getExecutions(campaign_id, limit, offset);

        function getExecutions(campaign_id, limit, offset) {
            return executionService.getExecutions(campaign_id, limit, offset).then(function(data) {
                var total_executions  = data.total;

                nb_fetched += data.results.length;
                groupExecutionsByCategory(data.results);

                if (nb_fetched < total_executions) {
                    return getExecutions(campaign_id, limit, offset + limit);
                }

                return categories;
            });
        }

        function groupExecutionsByCategory(executions) {
            executions.forEach(function(execution) {
                var category = execution.definition.category;
                if (! category) {
                    category = 'Uncategorized';
                    execution.definition._uncategorized = category;
                }

                if (typeof categories[category] === "undefined") {
                    categories[category] = {
                        label     : category,
                        executions: []
                    };
                }

                categories[category].executions.push(execution);
            });
        }
    }

    function resolveEnvironments(campaignService, $stateParams) {
        var environments = [],
            campaign_id  = $stateParams.id;

        return getEnvironments(campaign_id, 50, 0);

        function getEnvironments(campaign_id, limit, offset) {
            return campaignService.getEnvironments(campaign_id, limit, offset).then(function(data) {
                environments = environments.concat(data.results);

                if (environments.length < data.total) {
                    return getEnvironments(campaign_id, limit, offset + limit);
                }

                return environments;
            });
        }
    }

    function resolveAssignees(campaignService, $stateParams) {
        var assignees   = [],
            campaign_id = $stateParams.id;

        return getAssignees(campaign_id, 50, 0);

        function getAssignees(campaign_id, limit, offset) {
            return campaignService.getAssignees(campaign_id, limit, offset).then(function(data) {
                assignees = assignees.concat(data.results);

                if (assignees.length < data.total) {
                    return getAssignees(campaign_id, limit, offset + limit);
                }

                return assignees;
            });
        }
    }
})();
