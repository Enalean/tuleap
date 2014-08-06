angular
    .module('campaign')
    .service('CampaignService', CampaignService);

CampaignService.$inject = ['Restangular'];

function CampaignService(Restangular) {
    Restangular.setBaseUrl('/api/v1');

    return {
        getCampaigns: getCampaigns
    };

    function getCampaigns(project_id) {
        return Restangular.one('projects', project_id).all('campaigns').getList().$object;
    }
}