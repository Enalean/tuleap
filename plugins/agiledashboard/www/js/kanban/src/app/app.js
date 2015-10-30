(function () {
    angular.module('kanban', [
        'ui.router',
        'ui.tree',
        'ui.bootstrap',
        'socket',
        'templates-app',
        'shared-properties',
        'user-preferences',
        'restangular',
        'angularMoment',
        'ngSanitize',
        'gettext',
        'ngAnimate',
        'ngScrollbar',
        'tuleap.artifact-modal',
        'uuid-generator'
    ]);
})();