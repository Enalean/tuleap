import './empty-state.tpl.html';

export default EmptyState;

EmptyState.$inject = [];

function EmptyState() {
    return {
        restrict: 'E',
        templateUrl : 'empty-state.tpl.html',
        scope: {}
    };
}
