angular
    .module('backlog')
    .controller('BacklogController', BacklogController);

BacklogController.$inject = [
    'BacklogService'
];

function BacklogController(
    BacklogService
) {
    var self = this;
    _.extend(self, {
        details: BacklogService.backlog,
        items  : BacklogService.items,
        displayUserCantPrioritize: displayUserCantPrioritize
    });

    function hideUserCantPrioritize() {
        return BacklogService.backlog.user_can_move_cards || BacklogService.items.content.length === 0;
    }

    function displayUserCantPrioritize() {
        return ! hideUserCantPrioritize();
    }
}
