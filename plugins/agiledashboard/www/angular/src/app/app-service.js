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

services.factory('BacklogItem', ['$resource', function ($resource) {
    var url = '/api/v1/artifacts/:id';

    return $resource(url, {}, {
        children: {
            method: 'GET',
            url: url + '/children',
            isArray: true
        }
    });
}]);

services.factory('Artifact', ['$resource', function ($resource) {
    var url = '/api/v1/artifacts/:id';

    return $resource(url, {}, {
        reorder: {
            method: 'PATCH',
            url: url
        }
    });
}]);
