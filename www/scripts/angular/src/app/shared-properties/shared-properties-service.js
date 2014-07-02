var SharedPropertiesService = function () {
    var property = {
        campaign_tracker_id: undefined
    };

    return {
        getCampaignTrackerId: function () {
            return property.campaign_tracker_id;
        },
        setCampaignTrackerId: function (campaign_tracker_id) {
            property.campaign_tracker_id = campaign_tracker_id;
        }
    };
};