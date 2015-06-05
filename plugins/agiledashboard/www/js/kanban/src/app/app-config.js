(function () {
    angular
        .module('kanban')
        .config(KanbanConfig);

    KanbanConfig.$inject = ['$stateProvider', '$urlRouterProvider', 'RestangularProvider'];

    function KanbanConfig($stateProvider, $urlRouterProvider, RestangularProvider) {
        $urlRouterProvider.otherwise('/kanban');

        $stateProvider.state('kanban', {
            url: "/kanban",
            controller: 'KanbanCtrl',
            controllerAs: 'kanban',
            templateUrl: "kanban.tpl.html"
        });

        RestangularProvider.setDefaultHeaders({'Content-Type': 'application/json'});
    }
})();
