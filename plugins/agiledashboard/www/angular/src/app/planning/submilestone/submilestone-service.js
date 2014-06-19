var submilestoneService = function($resource) {
    var url = '/api/v1/milestones/:milestoneId';

    return $resource(url, {}, {
        content: {
            method: 'GET',
            url: url + '/content',
            isArray: true
        }
    });
};
