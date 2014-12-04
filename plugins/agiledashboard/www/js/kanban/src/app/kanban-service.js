(function () {
    angular
        .module('kanban')
        .service('KanbanService', KanbanService);

    KanbanService.$inject = ['Restangular', '$q'];

    function KanbanService(Restangular, $q) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getKanban: getKanban
        };

        function getKanban(id) {
            var data = $q.defer();

            rest
                .one('kanban', id)
                .get()
                .then(function (response) {
                    data.resolve(response.data);
                });

            return data.promise;
        }
    }
})();