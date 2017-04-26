angular
    .module('campaign')
    .service('ExecutionRestService', ExecutionRestService);

ExecutionRestService.$inject = [
    'Restangular',
    'SharedPropertiesService'
];

function ExecutionRestService(
    Restangular,
    SharedPropertiesService
) {
    _.extend(Restangular.configuration.defaultHeaders, {
        'X-Client-UUID': SharedPropertiesService.getUUID()
    });

    var self    = this,
        baseurl = '/api/v1',
        rest    = Restangular.withConfig(setRestangularConfig);

    _.extend(self, {
        getRemoteExecutions          : getRemoteExecutions,
        postTestExecution            : postTestExecution,
        putTestExecution             : putTestExecution,
        changePresenceOnTestExecution: changePresenceOnTestExecution,
        leaveTestExecution           : leaveTestExecution
    });

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getRemoteExecutions(campaign_id, limit, offset) {
        return rest.one('trafficlights_campaigns', campaign_id)
            .all('trafficlights_executions')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                var result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function postTestExecution(tracker_id, definition_id, status) {
        return rest
            .one('trafficlights_executions')
            .post('execution', {
                tracker      : {id: tracker_id},
                definition_id: definition_id,
                status       : status
            })
            .then(function (response) {
                return response.data;
            });
    }

    function putTestExecution(execution_id, new_status, time, results) {
        return rest
            .one('trafficlights_executions', execution_id)
            .put({
                status: new_status,
                time: time,
                results: results
            })
            .then(function (response) {
                return response.data;
            });
    }

    function changePresenceOnTestExecution(execution_id, old_execution_id) {
        return rest
            .one('trafficlights_executions', execution_id)
            .all('presences')
            .patch({
                uuid: SharedPropertiesService.getUUID(),
                remove_from: old_execution_id
            });
    }

    function leaveTestExecution(execution_id) {
        return changePresenceOnTestExecution(execution_id, execution_id);
    }
}
