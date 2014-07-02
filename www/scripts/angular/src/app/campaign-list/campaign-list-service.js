var campaign_list_service = function(Restangular) {

    Restangular.setBaseUrl('/api/v1');

    return {
        campaigns: function (campaign_tracker_id) {
            return Restangular.one('trackers', campaign_tracker_id).all('artifacts').getList().$object;
        }
    };
};