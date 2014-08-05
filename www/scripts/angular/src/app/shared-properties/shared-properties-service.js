var SharedPropertiesService = function () {
    var property = {
        project_id: undefined,
        test_definition_tracker_id: undefined,
        test_execution_tracker_id: undefined
    };

    return {
        getProjectId: function () {
            return property.project_id;
        },
        setProjectId: function (project_id) {
            property.project_id = project_id;
        },
        getTestDefinitionTrackerId: function () {
            return property.test_definition_tracker_id;
        },
        setTestDefinitionTrackerId: function (test_definition_tracker_id) {
            property.test_definition_tracker_id = test_definition_tracker_id;
        },
        getTestExecutionTrackerId: function () {
            return property.test_execution_tracker_id;
        },
        setTestExecutionTrackerId: function(test_execution_tracker_id) {
            property.test_execution_tracker_id = test_execution_tracker_id;
        }
    };
};