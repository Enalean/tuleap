import _ from 'lodash';

import './execution-presences.tpl.html';

export default ExecutionService;

ExecutionService.$inject = [
    '$q',
    '$rootScope',
    '$modal',
    'ExecutionConstants',
    'ExecutionRestService'
];

function ExecutionService(
    $q,
    $rootScope,
    $modal,
    ExecutionConstants,
    ExecutionRestService
) {
    var self = this;

    _.extend(self, {
        initialization                    : initialization,
        resetExecutions                   : resetExecutions,
        loadExecutions                    : loadExecutions,
        getExecutions                     : getExecutions,
        getExecutionsByDefinitionId       : getExecutionsByDefinitionId,
        addPresenceCampaign               : addPresenceCampaign,
        updateCampaign                    : updateCampaign,
        addTestExecutions                 : addTestExecutions,
        updateTestExecution               : updateTestExecution,
        updatePresenceOnCampaign          : updatePresenceOnCampaign,
        removeAllPresencesOnCampaign      : removeAllPresencesOnCampaign,
        viewTestExecution                 : viewTestExecution,
        removeAllViewTestExecution        : removeAllViewTestExecution,
        removeViewTestExecution           : removeViewTestExecution,
        removeViewTestExecutionByUUID     : removeViewTestExecutionByUUID,
        removePresenceCampaign            : removePresenceCampaign,
        displayPresencesForAllExecutions  : displayPresencesForAllExecutions,
        displayPresencesByExecution       : displayPresencesByExecution,
        displayError                      : displayError,
        showPresencesModal                : showPresencesModal
    });

    initialization();

    function initialization() {
        _.extend(self, {
            UNCATEGORIZED                        : ExecutionConstants.UNCATEGORIZED,
            campaign                             : {},
            executions_by_categories_by_campaigns: {},
            executions                           : {},
            categories                           : {},
            loading                              : {},
            presences_loaded                     : false,
            executions_loaded                    : false,
            presences_by_execution               : {},
            presences_on_campaign                : []
        });
    }

    function resetExecutions(campaign_id) {
        _.forEach(self.executions_by_categories_by_campaigns[campaign_id], function(category) {
            _.forEach(category.executions, function(execution) {
                delete self.executions[execution.id];
            });
        });
        delete self.executions_by_categories_by_campaigns[campaign_id];
    }

    function loadExecutions(campaign_id) {
        var deferred = $q.defer();
        self.campaign_id = campaign_id;

        if (self.executions_by_categories_by_campaigns[campaign_id]) {
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
            var total_executions = data.total;

            nb_fetched += data.results.length;
            groupExecutionsByCategory(campaign_id, data.results);

            $rootScope.$emit('bunchOfExecutionsLoaded', data.results);

            offset = offset + limit;
            if (offset < total_executions) {
                return getExecutions(campaign_id, limit, offset, nb_fetched);
            } else {
                self.loading[campaign_id] = false;
                return;
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

    function addTestExecutions(executions) {
        if (! _.isArray(executions)) {
            executions = [executions];
        }
        groupExecutionsByCategory(self.campaign_id, executions);

        _.forEach(executions, function(execution) {
            var status = execution.status;
            self.campaign['nb_of_' + status]++;
        });
    }

    function updateTestExecution(execution_updated) {
        var execution       = self.executions[execution_updated.id];
        var previous_status = execution.previous_result.status;
        var status          = execution_updated.status;

        if (execution) {
            _.assign(execution, execution_updated);
        }

        execution.saving       = false;
        execution.submitted_by = null;
        execution.error        = '';
        execution.results      = '';

        self.campaign['nb_of_' + status]++;
        self.campaign['nb_of_' + previous_status]--;
    }

    function updatePresenceOnCampaign(user) {
        var user_on_campaign = _.find(self.presences_on_campaign, function(presence) {
            return presence.id === user.id;
        });

        if (user_on_campaign && ! _.has(user_on_campaign, 'score')) {
            _.extend(user_on_campaign, user.score);
        }

        if (user_on_campaign && user_on_campaign.score !== user.score) {
            user_on_campaign.score = user.score;
        }

        if (! user_on_campaign) {
            addPresenceCampaign(user);
        }
    }

    function updateCampaign(new_campaign) {
        self.campaign = new_campaign;
    }

    function addPresenceCampaign(user) {
        var user_id_exists = _.some(self.presences_on_campaign, function(presence) {
            return presence.id === user.id;
        });

        if (! user_id_exists) {
            self.presences_on_campaign.push(user);
        } else if (_.has(user, 'score')) {
            _.extend(user_id_exists, user.score);
        }
    }

    function viewTestExecution(execution_id, user) {
        if (_.has(self.executions, execution_id)) {
            var execution = self.executions[execution_id];

            if (! _.has(execution, 'viewed_by')) {
                execution.viewed_by = [];
            }

            var user_uuid_exists = _.some(execution.viewed_by, function(presence) {
                return presence.uuid === user.uuid;
            });

            if (! user_uuid_exists) {
                execution.viewed_by.push(user);
            }
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
        var user = {};

        _.forEach(self.executions, function(execution) {
            _.remove(execution.viewed_by, function(presence) {
                if (presence.uuid === uuid) {
                    user = presence;
                }
                return presence.uuid === uuid;
            });
        });
    }

    function removePresenceCampaign(user) {
        var user_found = _.some(self.executions, function(execution) {
            return _.some(execution.viewed_by, function(presence) {
                return presence.id === user.id;
            });
        });

        if (! user_found) {
            _.remove(self.presences_on_campaign, function(presence) {
                return presence.id === user.id;
            });
        }
    }

    function removeAllPresencesOnCampaign() {
        self.presences_on_campaign = [];
    }

    function displayPresencesByExecution(execution_id, presences) {
        if (_.has(self.executions, execution_id)) {
            self.executions[execution_id].viewed_by = presences;
        }
    }

    function displayPresencesForAllExecutions() {
        if (self.presences_loaded && self.executions_loaded) {
            self.presences_loaded  = false;
            self.executions_loaded = false;
            _.forEach(self.presences_by_execution, function (presences, execution_id) {
                _.forEach(presences, function (presence) {
                    viewTestExecution(execution_id, presence);
                    addPresenceCampaign(presence);
                });
            });
        }
    }

    function displayError(execution, response) {
        execution.saving = false;
        execution.error  = response.status + ': ' + response.data.error.message;
    }

    function showPresencesModal() {
        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'execution-presences.tpl.html',
            controller : 'ExecutionPresencesCtrl as modal',
            resolve: {
                modal_model: function () {
                    return {
                        presences: self.presences_on_campaign
                    };
                }
            }
        });
    }
}

