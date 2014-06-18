var services = angular.module('planningServices', ['ngResource']);

services.factory('Milestone', ['$resource', function ($resource) {
    var url = '/api/v1/milestones/:milestoneId';

    return $resource(url, {}, {
        backlog: {
            method: 'GET',
            url: url + '/backlog',
            isArray: true
        },
        update_backlog: {
            method: 'PATCH',
            url: url + '/backlog'
        },
        milestones: {
            method: 'GET',
            url: url + '/milestones',
            isArray: true
        }
    });
}]);
