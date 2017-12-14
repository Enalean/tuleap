import _ from 'lodash';

export default ExecutionService;

ExecutionService.$inject = [
    '$q',
    '$rootScope',
    'ExecutionConstants',
    'ExecutionRestService',
];

function ExecutionService(
    $q,
    $rootScope,
    ExecutionConstants,
    ExecutionRestService,
) {
    var self = this;

    Object.assign(self, {
        initialization,
        synchronizeExecutions,
        loadExecutions,
        getAllRemoteExecutions,
        getExecutionsByDefinitionId,
        addPresenceCampaign,
        updateCampaign,
        addTestExecution,
        addTestExecutionWithoutUpdateCampaignStatus,
        removeTestExecution,
        removeTestExecutionWithoutUpdateCampaignStatus,
        updateTestExecution,
        updatePresenceOnCampaign,
        removeAllPresencesOnCampaign,
        viewTestExecution,
        removeAllViewTestExecution,
        removeViewTestExecution,
        removeViewTestExecutionByUUID,
        displayPresencesForAllExecutions,
        displayPresencesByExecution,
        displayError,
        displayErrorMessage,
        executionsForCampaign,
        addArtifactLink,
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

    function synchronizeExecutions(campaign_id) {
        var remote_executions = [],
            limit = 50,
            offset = 0;

        return getAllRemoteExecutions(campaign_id, limit, offset)
            .then(function(remote_executions) {
                var executions_to_remove = _.select(self.executions, function(execution) {
                    return ! _.some(remote_executions, { id: execution.id });
                });
                var executions_to_add = _.select(remote_executions, function(execution) {
                    return ! _.some(self.executions, { id: execution.id });
                });

                _.forEach(executions_to_remove, removeTestExecutionWithoutUpdateCampaignStatus);
                _.forEach(executions_to_add, addTestExecutionWithoutUpdateCampaignStatus);
            });
    }

    function loadExecutions(campaign_id) {
        var deferred = $q.defer();
        self.campaign_id = campaign_id;

        if (self.executions_by_categories_by_campaigns[campaign_id]) {
            deferred.resolve();
            return deferred.promise;
        }

        var remote_executions = [],
            limit             = 50,
            offset            = 0;

        self.loading[campaign_id] = true;
        self.executions_by_categories_by_campaigns[campaign_id] = {};

        return getAllRemoteExecutions(campaign_id, limit, offset).then(function(executions) {
            self.loading[campaign_id] = false;
            return;
        });
    }

    function getAllRemoteExecutions(campaign_id, limit, offset, remote_executions) {
        remote_executions = remote_executions || [];

        return ExecutionRestService.getRemoteExecutions(campaign_id, limit, offset).then(function(data) {
          var total_executions = data.total;

          groupExecutionsByCategory(campaign_id, data.results);
          $rootScope.$emit('bunchOfExecutionsLoaded', data.results);
          remote_executions = remote_executions.concat(data.results);

          offset = offset + limit;
          if (offset < total_executions) {
              return getAllRemoteExecutions(campaign_id, limit, offset, remote_executions);
          } else {
              return remote_executions;
          }
        });
    }

    function groupExecutionsByCategory(campaign_id, executions) {
        var categories = self.executions_by_categories_by_campaigns[campaign_id];

        _.forEach(executions, function(execution) {
            var category = execution.definition.category;
            if (! category) {
                category = ExecutionConstants.UNCATEGORIZED;
                execution.definition._uncategorized = category;
            }

            if (! _.has(executions, execution.id)) {
                self.executions[execution.id] = execution;
            }

            if (typeof categories[category] === "undefined") {
                categories[category] = {
                    label     : category,
                    executions: []
                };
            }

            if (! _.some(categories[category].executions, { id: execution.id })) {
                categories[category].executions.push(execution);
            }
        });

        self.categories = categories;
    }

    function getExecutionsByDefinitionId(artifact_id) {
        var executions = _.flatten(_.map(self.categories, 'executions'));

        return _.filter(executions, { definition: { id: artifact_id } });
    }

    function addTestExecution(execution) {
        var executions = [execution];
        var status = execution.status;

        groupExecutionsByCategory(self.campaign_id, executions);
        self.campaign['nb_of_' + status]++;
        self.campaign.total++;
    }

    function addTestExecutionWithoutUpdateCampaignStatus(execution) {
        var executions = [execution];

        groupExecutionsByCategory(self.campaign_id, executions);
    }

    function removeTestExecution(execution_to_remove) {
        removeTestExecutionByCategories(execution_to_remove.id);
        self.campaign['nb_of_' + execution_to_remove.status]--;
        self.campaign.total--;
    }

    function removeTestExecutionWithoutUpdateCampaignStatus(execution_to_remove) {
        removeTestExecutionByCategories(execution_to_remove.id);
    }

    function removeTestExecutionByCategories(execution_to_remove_id) {
        for (const category of Object.values(self.executions_by_categories_by_campaigns[self.campaign_id])) {
            _.remove(category.executions, { id: execution_to_remove_id });
        }
        delete self.executions[execution_to_remove_id];
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
            _.remove(execution.viewed_by);
        });
    }

    function removeViewTestExecutionByUUID(uuid) {
        _.forEach(self.executions, function(execution) {
            _.remove(execution.viewed_by, { uuid: uuid });
        });
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

    function displayErrorMessage(execution, message) {
        execution.saving = false;
        execution.error  = message;
    }

    function executionsForCampaign(campaign_id) {
        var executions = _.map(
            self.executions_by_categories_by_campaigns[campaign_id], 'executions'
        );
        return _.flatten(executions);
    }

    function addArtifactLink(execution_id, artifact_link) {
        if (! _.has(self.executions, execution_id)) {
            return;
        }
        const execution = self.executions[execution_id];

        execution.linked_bugs.push(artifact_link);
    }
}
