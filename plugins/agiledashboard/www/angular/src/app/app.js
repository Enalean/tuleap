angular.module('tuleap.planningApp', [
    'ui.tree',
    'infinite-scroll',
    controllers.name,
    services.name,
    'tuleap.planning.submilestone'
]).directive('scrollable', function() {
    return {
        link: function (scope, element, attrs) {
            element.jScrollPane({
                autoReinitialise: true,
                verticalGutter: 0
            });
        }
    };
});