(function () {
    angular
        .module('campaign')
        .service('ExecutionService', ExecutionService)
        .constant("ExecutionConstants", {
            "UNCATEGORIZED": "Uncategorized"
        });

    ExecutionService.$inject = ['Restangular', '$q', '$rootScope', 'ExecutionConstants'];

    function ExecutionService(Restangular, $q, $rootScope, ExecutionConstants) {
        var self    = this,
            baseurl = '/api/v1',
            rest    = Restangular.withConfig(setRestangularConfig);

        _.extend(self, {
            UNCATEGORIZED                         : ExecutionConstants.UNCATEGORIZED,
            executions_by_categories_by_campaigns : {},
            executions                            : {},
            categories                            : {},
            loading                               : {},
            loadExecutions                        : loadExecutions,
            getExecutionsByDefinitionId           : getExecutionsByDefinitionId
        });

        function loadExecutions(campaign_id) {
            self.campaign_id = campaign_id;

            if (self.executions_by_categories_by_campaigns[campaign_id]) {
                return;
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
    }
})();