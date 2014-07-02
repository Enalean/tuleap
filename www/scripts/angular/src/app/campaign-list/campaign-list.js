var campaign_list = angular.module('campaign-list', [
    'restangular',
    'templates-app',
    'shared-properties'
]);

campaign_list.service('campaign-list-service', [
    'Restangular',
    campaign_list_service
]);

campaign_list.controller('campaign-list-controller', [
    '$scope',
    'campaign-list-service',
    'shared-properties-service',
    campaign_list_controller
]);

campaign_list.config(function ($stateProvider) {
    $stateProvider.state('campaigns', {
        url: '/campaigns',
        views: {
            "main": {
                controller: 'campaign-list-controller',
                templateUrl: 'campaign-list/campaign-list.tpl.html'
            }
        }
    });
});