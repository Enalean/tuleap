angular
    .module('sharedProperties')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService() {
    var property = {
        project_id: undefined,
        test_definition_tracker_id: undefined,
        test_execution_tracker_id: undefined
    };

    return {
        getProjectId: getProjectId,
        setProjectId: setProjectId,
        getTestDefinitionTrackerId: getTestDefinitionTrackerId,
        setTestDefinitionTrackerId: setTestDefinitionTrackerId,
        getTestExecutionTrackerId: getTestExecutionTrackerId,
        setTestExecutionTrackerId: setTestExecutionTrackerId
    };

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getTestDefinitionTrackerId() {
        return property.test_definition_tracker_id;
    }

    function setTestDefinitionTrackerId(test_definition_tracker_id) {
        property.test_definition_tracker_id = test_definition_tracker_id;
    }

    function getTestExecutionTrackerId() {
        return property.test_execution_tracker_id;
    }

    function setTestExecutionTrackerId(test_execution_tracker_id) {
        property.test_execution_tracker_id = test_execution_tracker_id;
    }
}