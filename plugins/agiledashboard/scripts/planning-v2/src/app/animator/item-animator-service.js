export default ItemAnimatorService;

ItemAnimatorService.$inject = ["$timeout"];

function ItemAnimatorService($timeout) {
    const self = this;
    Object.assign(self, {
        animateUpdated,
        animateCreated,
    });

    function animateUpdated(backlog_item) {
        backlog_item.updated = true;
        $timeout(() => {
            backlog_item.updated = false;
        }, 1500);
    }

    function animateCreated(backlog_item) {
        backlog_item.created = true;
        $timeout(() => {
            backlog_item.created = false;
        }, 1500);
    }
}
