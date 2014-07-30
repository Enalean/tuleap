var SharedPropertiesService = function () {
    var property = {
        campaign_tracker_id: undefined,
        test_definition_tracker_id: undefined,
        test_execution_tracker_id: undefined
    };

    return {
        getCampaignTrackerId: function () {
            return property.campaign_tracker_id;
        },
        setCampaignTrackerId: function (campaign_tracker_id) {
            property.campaign_tracker_id = campaign_tracker_id;
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