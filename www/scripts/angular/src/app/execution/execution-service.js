angular
    .module('campaign')
    .service('ExecutionService', ExecutionService);

ExecutionService.$inject = ['Restangular', '$q', '$rootScope'];

function ExecutionService(Restangular, $q, $rootScope) {
    var self    = this,
        baseurl = '/api/v1',
        rest    = Restangular.withConfig(setRestangularConfig);

    self.executions_by_categories_by_campaigns = {};
    self.executions                            = {};
    self.loading                               = {};
    self.loadExecutions                        = loadExecutions;

    function loadExecutions(campaign_id) {
        if (self.executions_by_categories_by_campaigns[campaign_id]) {
            return;
        }

        var limit      = 50,
            offset     = 0,
            nb_fetched = 0;

        self.loading[campaign_id] = true;
        self.executions_by_categories_by_campaigns[campaign_id] = {};
        getExecutions(limit, offset);

        function getExecutions(limit, offset) {
            getRemoteExecutions(campaign_id, limit, offset).then(function(data) {
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
                    category = 'Uncategorized';
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
}