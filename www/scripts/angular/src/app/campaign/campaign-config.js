angular
    .module('campaign')
    .config(CampaignConfig);

CampaignConfig.$inject = ['$stateProvider'];

function CampaignConfig($stateProvider) {
    $stateProvider
        .state('campaigns', {
            abstract:   true,
            url:        '/campaigns',
            template:   '<ui-view />',
            controller: 'CampaignCtrl',
            resolve: {
                milestone: function(SharedPropertiesService) {
                    return SharedPropertiesService.getCurrentMilestone();
                }
            },
        })
        .state('campaigns.milestone', {
            url: '/milestone',
            data: {
                ncyBreadcrumbLabel: '{{ milestone.label }}'
            },
            onEnter: function($window, milestone) {
                $window.open(milestone.uri, '_self');
            }
        })
        .state('campaigns.list', {
            url:         '',
            controller:  'CampaignListCtrl',
            templateUrl: 'campaign/campaign-list.tpl.html',
            data: {
                ncyBreadcrumbLabel: '{{ campaign_breadcrumb_label }}'
            }
        });
}
