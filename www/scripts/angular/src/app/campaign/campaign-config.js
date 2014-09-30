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
            data: {
                ncyBreadcrumbLabel: '{{ campaign_breadcrumb_label }}'
            }
        })
        .state('campaigns.list', {
            authenticate: true,
            url:         '',
            controller:  'CampaignListCtrl',
            templateUrl: 'campaign/campaign-list.tpl.html'
        })
        .state('campaigns.new', {
            authenticate: true,
            url:         '/new',
            controller:  'CampaignNewCtrl',
            templateUrl: 'campaign/campaign-new.tpl.html',
            data: {
                ncyBreadcrumbLabel: '{{ breadcrumb_label }}',
                ncyBreadcrumbParent: 'campaigns.list'
            }
        });
}
