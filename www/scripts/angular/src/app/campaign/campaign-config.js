angular
    .module('campaign')
    .config(CampaignConfig);

CampaignConfig.$inject = ['$stateProvider'];

function CampaignConfig($stateProvider) {
    $stateProvider
        .state('campaigns', {
            abstract: true,
            url: '/campaigns',
            template: '<ui-view />',
            controller: 'CampaignCtrl',
            data: {
                ncyBreadcrumbLabel: 'Campaigns'
            }
        })
        .state('campaigns.list', {
            url: '',
            controller: 'CampaignListCtrl',
            templateUrl: 'campaign/campaign-list.tpl.html'
        });
}
