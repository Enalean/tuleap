(function () {
    angular.module('planning', [
        'ui.router',
        'ui.tree',
        'templates-app',
        'gettext',
        'angularMoment',
        'ngSanitize',
        'shared-properties',
        'backlog-item',
        'milestone',
        'project'
    ]);
})();
