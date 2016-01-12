angular
    .module('backlog-item-selected')
    .directive('backlogItemSelectedBar', BacklogItemSelectedBar);

BacklogItemSelectedBar.$inject = [];

function BacklogItemSelectedBar() {
    return {
        restrict        : 'A',
        scope           : false,
        controller      : 'BacklogItemSelectedBarController as selected_bar_controller',
        templateUrl     : 'backlog-item-selected/backlog-item-selected-bar.tpl.html',
        bindToController: true
    };
}
