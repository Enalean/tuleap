(function () {
    angular
        .module('milestone')
        .service('MilestoneService', MilestoneService);

    MilestoneService.$inject = ['Restangular', '$q'];

    function MilestoneService(Restangular, $q) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getSubMilestones: getSubMilestones,
            getMilestones   : getMilestones
        };

        function getMilestones(project_id, limit, offset) {
            var data = $q.defer();

            rest.one('projects', project_id)
                .all('milestones')
                .getList({
                    limit: limit,
                    offset: offset
                })
                .then(function(response) {
                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getSubMilestones(milestone_id, limit, offset) {
            var data = $q.defer();

            rest.one('milestones', milestone_id)
                .all('milestones')
                .getList({
                    limit: limit,
                    offset: offset
                })
                .then(function(response) {
                    result = {
                        results: response.data,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }
    }
})();