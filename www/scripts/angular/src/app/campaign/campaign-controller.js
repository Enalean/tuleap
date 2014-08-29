angular
    .module('campaign')
    .controller('CampaignCtrl', CampaignCtrl);

CampaignCtrl.$inject = ['$state','gettextCatalog'];

function CampaignCtrl($state, gettextCatalog) {
    $state.current.data.ncyBreadcrumbLabel = gettextCatalog.getString('Campaigns');
}
