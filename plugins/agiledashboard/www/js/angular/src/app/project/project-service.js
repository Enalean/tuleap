(function () {
    angular
        .module('project')
        .service('ProjectService', ProjectService);

    ProjectService.$inject = ['Restangular', '$q'];

    function ProjectService(Restangular, $q, BacklogItemFactory) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            reorderBacklog : reorderBacklog
        };

        function reorderBacklog(project_id, dropped_item_id, compared_to) {
            return rest.one('projects', project_id)
                .all('backlog')
                .patch({
                    order: {
                        ids         : [dropped_item_id],
                        direction   : compared_to.direction,
                        compared_to : compared_to.item_id
                    }
                });
        }
    }
})();