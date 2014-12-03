(function () {
    angular
        .module('kanban')
        .config(KanbanConfig);

    KanbanConfig.$inject = ['$stateProvider', '$urlRouterProvider'];

    function KanbanConfig($stateProvider, $urlRouterProvider) {
        $urlRouterProvider.otherwise('/kanban');

        $stateProvider.state('kanban', {
            url: "/kanban",
            controller: 'KanbanCtrl',
            controllerAs: 'kanban',
            templateUrl: "kanban.tpl.html"
        });
    }
})();
