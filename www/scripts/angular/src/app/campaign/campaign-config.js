angular
    .module('campaign')
    .config(CampaignConfig);

CampaignConfig.$inject = ['$stateProvider'];

function CampaignConfig($stateProvider) {
    $stateProvider.state('campaigns', {
        url: '/campaigns',
        views: {
            "main": {
                controller: 'CampaignListCtrl',
                templateUrl: 'campaign/campaign-list.tpl.html'
            }
        }
    });
}