angular
    .module('campaign')
    .controller('CampaignCtrl', CampaignCtrl);

CampaignCtrl.$inject = ['$scope','gettextCatalog'];

function CampaignCtrl($scope, gettextCatalog) {
    $scope.campaign_breadcrumb_label = gettextCatalog.getString('Campaigns');
}
