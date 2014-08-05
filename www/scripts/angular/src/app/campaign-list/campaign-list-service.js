var campaign_list_service = function(Restangular) {

    Restangular.setBaseUrl('/api/v1');

    return {
        campaigns: function (project_id) {
            return Restangular.one('projects', project_id).all('campaigns').getList().$object;
        }
    };
};