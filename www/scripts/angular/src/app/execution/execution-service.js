angular
    .module('campaign')
    .service('ExecutionService', ExecutionService)
    .constant("ExecutionConstants", {
        "UNCATEGORIZED": "Uncategorized"
    });

ExecutionService.$inject = [
    '$q',
    'Restangular',
    '$rootScope',
    'ExecutionConstants',
    'SharedPropertiesService'
];

function ExecutionService(
    $q,
    Restangular,
    $rootScope,
    ExecutionConstants,
    SharedPropertiesService
) {
    var self    = this,
        baseurl = '/api/v1',
        rest    = Restangular.withConfig(setRestangularConfig);

    _.extend(self, {
        UNCATEGORIZED                         : ExecutionConstants.UNCATEGORIZED,
        campaign                              : {},
        executions_by_categories_by_campaigns : {},
        executions                            : {},
        categories                            : {},
        loading                               : {},
        loadExecutions                        : loadExecutions,
        getExecutionsByDefinitionId           : getExecutionsByDefinitionId,
        updateCampaign                        : updateCampaign,
        updateTestExecution                   : updateTestExecution,
        putTestExecution                      : putTestExecution,
        viewTestExecution                     : viewTestExecution,
        removeViewTestExecution               : removeViewTestExecution,
        getGlobalPositions                    : getGlobalPositions
    });

    function loadExecutions(campaign_id) {
        self.campaign_id = campaign_id;

        if (self.executions_by_categories_by_campaigns[campaign_id]) {
            var deferred = $q.defer();
            deferred.resolve();
            return deferred.promise;
        }

        var limit      = 50,
            offset     = 0,
            nb_fetched = 0;

        self.loading[campaign_id] = true;
        self.executions_by_categories_by_campaigns[campaign_id] = {};
        return getExecutions(limit, offset);

        function getExecutions(limit, offset) {
            return getRemoteExecutions(campaign_id, limit, offset).then(function(data) {
                var total_executions  = data.total;

                nb_fetched += data.results.length;
                groupExecutionsByCategory(data.results);

                $rootScope.$emit('bunchOfExecutionsLoaded', data.results);

                if (nb_fetched < total_executions) {
                    getExecutions(limit, offset + limit);
                } else {
                    self.loading[campaign_id] = false;
                }
            });
        }

        function groupExecutionsByCategory(executions) {
            executions.forEach(function(execution) {
                var category = execution.definition.category;
                if (! category) {
                    category = ExecutionConstants.UNCATEGORIZED;
                    execution.definition._uncategorized = category;
                }

                self.executions[execution.id] = execution;

                if (typeof self.executions_by_categories_by_campaigns[campaign_id][category] === "undefined") {
                    self.executions_by_categories_by_campaigns[campaign_id][category] = {
                        label     : category,
                        executions: []
                    };
                }

                self.executions_by_categories_by_campaigns[campaign_id][category].executions.push(execution);
            });

            self.categories = self.executions_by_categories_by_campaigns[campaign_id];
        }

        function getRemoteExecutions(campaign_id, limit, offset) {
            var data = $q.defer();

            rest.one('trafficlights_campaigns', campaign_id)
                .all('trafficlights_executions')
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
    }

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getExecutionsByDefinitionId(artifact_id) {
        var executions = [];
        _(self.categories).forEach(function(category) {
            _(category.executions).forEach(function(execution) {
                if (execution.definition.id === artifact_id) {
                    executions.push(execution);
                }
            });
        });

        return executions;
    }

    function updateTestExecution(execution_to_save) {
        var execution       = self.executions[execution_to_save.id];
        var previous_status = execution.status;

        execution.saving = false;
        _.assign(execution, execution_to_save);

        execution.previous_result.status       = previous_status;
        execution.previous_result.submitted_on = new Date();
        execution.previous_result.submitted_by = execution.submitted_by;
        execution.previous_result.result       = execution.results;
        execution.submitted_by                 = null;
        execution.results                      = '';
        execution.error                        = '';

        switch (execution.status) {
            case 'passed':
                self.campaign.nb_of_passed++;
                break;
            case 'failed':
                self.campaign.nb_of_failed++;
                break;
            case 'blocked':
                self.campaign.nb_of_blocked++;
                break;
        }

        switch (previous_status) {
            case 'passed':
                self.campaign.nb_of_passed--;
                break;
            case 'failed':
                self.campaign.nb_of_failed--;
                break;
            case 'blocked':
                self.campaign.nb_of_blocked--;
                break;
            default:
                self.campaign.nb_of_not_run--;
                break;
        }
    }

    function updateCampaign(new_campaign) {
        self.campaign = new_campaign;
    }

    function putTestExecution(execution_id, new_status, results) {
        return rest
            .one('trafficlights_executions', execution_id)
            .put({
                status: new_status,
                results: results
            })
            .then(function (response) {
                return response;
            });
    }

    function viewTestExecution(execution_id) {
        /**
         * TODO: Request Http to Tuleap server (POST /test/{id}/presence)
         */
        if (_.has(self.executions, execution_id)) {
            self.executions[execution_id].viewed_by = SharedPropertiesService.getCurrentUser();
        }
    }

    function removeViewTestExecution(execution_id) {
        if (_.has(self.executions, execution_id)) {
            self.executions[execution_id].viewed_by = null;
        }
    }

    function getGlobalPositions() {
        /**
         * TODO: Request Http to Tuleap server (GET)
         */
        var response = [];
        response.forEach(function(element) {
            self.executions[element.id].viewed_by = element.user;
        });
    }
}