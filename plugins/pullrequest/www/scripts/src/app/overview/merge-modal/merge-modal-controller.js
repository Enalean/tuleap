export default MergeModalController;

MergeModalController.$inject = ["$modalInstance"];

function MergeModalController($modalInstance) {
    const self = this;

    Object.assign(self, {
        proceed,
        cancel
    });

    function proceed() {
        $modalInstance.close();
    }

    function cancel() {
        $modalInstance.dismiss();
    }
}
