var campaign_list = angular.module('campaign-list', ['restangular']);

campaign_list.service('campaign-list-service', [
    'Restangular',
    campaign_list_service
]);

campaign_list.controller('campaign-list-controller', [
    '$scope',
    'campaign-list-service',
    campaign_list_controller
]);