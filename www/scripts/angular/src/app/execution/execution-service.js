angular
    .module('campaign')
    .service('ExecutionService', ExecutionService)
    .constant("ExecutionConstants", {
        "UNCATEGORIZED": "Uncategorized"
    });

ExecutionService.$inject = [
    '$q',
    '$rootScope',
    'ExecutionConstants',
    'ExecutionRestService'
];

function ExecutionService(
    $q,
    $rootScope,
    ExecutionConstants,
    ExecutionRestService
) {
    var self = this;

    _.extend(self, {
        loadExecutions               : loadExecutions,
        getExecutions                : getExecutions,
        getExecutionsByDefinitionId  : getExecutionsByDefinitionId,
        updateCampaign               : updateCampaign,
        updateTestExecution          : updateTestExecution,
        viewTestExecution            : viewTestExecution,
        removeAllViewTestExecution   : removeAllViewTestExecution,
        removeViewTestExecution      : removeViewTestExecution,
        removeViewTestExecutionByUUID: removeViewTestExecutionByUUID,
        getGlobalPositions           : getGlobalPositions,
        displayError                 : displayError
    });

    initialization();

    $rootScope.$on('service-reload', function() {
        initialization();
    });

    function initialization() {
        _.extend(self, {
            UNCATEGORIZED                        : ExecutionConstants.UNCATEGORIZED,
            campaign                             : {},
            executions_by_categories_by_campaigns: {},
            executions                           : {},
            categories                           : {},
            loading                              : {}
        });
    }

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
        return getExecutions(campaign_id, limit, offset, nb_fetched);
    }

    function getExecutions(campaign_id, limit, offset, nb_fetched) {
        return ExecutionRestService.getRemoteExecutions(campaign_id, limit, offset).then(function(data) {
            var total_executions  = data.total;

            nb_fetched += data.results.length;
            groupExecutionsByCategory(campaign_id, data.results);

            $rootScope.$emit('bunchOfExecutionsLoaded', data.results);

            if (nb_fetched < total_executions) {
                getExecutions(campaign_id, limit, offset + limit);
            } else {
                self.loading[campaign_id] = false;
            }
        });
    }

    function groupExecutionsByCategory(campaign_id, executions) {
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

    function updateTestExecution(execution_updated) {
        var execution = self.executions[execution_updated.id];
        var previous_status = execution.previous_result.status;

        _.assign(execution, execution_updated);

        execution.saving       = false;
        execution.submitted_by = null;
        execution.error        = '';
        execution.results      = '';

        self.campaign[('nb_of_').concat(execution_updated.status)]++;
        self.campaign[('nb_of_').concat(previous_status)]--;
    }

    function updateCampaign(new_campaign) {
        self.campaign = new_campaign;
    }

    function viewTestExecution(execution_id, user) {
        if (_.has(self.executions, execution_id)) {
            var execution = self.executions[execution_id];
            if (! _.has(execution, 'viewed_by')) {
                execution.viewed_by = [];
            }
            execution.viewed_by.push(user);
        }
    }

    function removeViewTestExecution(execution_id, user_to_remove) {
        if (_.has(self.executions, execution_id)) {
            _.remove(self.executions[execution_id].viewed_by, function(user) {
                return user.id === user_to_remove.id && user.uuid === user_to_remove.uuid;
            });
        }
    }

    function removeAllViewTestExecution() {
        _.forEach(self.executions, function(execution) {
            _.remove(self.executions[execution.id].viewed_by);
        });
    }

    function removeViewTestExecutionByUUID(uuid) {
        _.forEach(self.executions, function(execution) {
            _.remove(execution.viewed_by, function(presence) {
                return presence.uuid === uuid;
            });
        });
    }

    function getGlobalPositions() {
        /**
         * TODO: Request Http to Tuleap server (GET)
         */
        var response = [];
        response.forEach(function(element) {
            var execution = self.executions[element.id];
            if (! _.has(execution, 'viewed_by')) {
                execution.viewed_by = [];
            }
            execution.viewed_by.push(element.user);
        });
    }

    function displayError(execution, response) {
        execution.saving = false;
        execution.error  = response.status + ': ' + response.data.error.message;
    }
}