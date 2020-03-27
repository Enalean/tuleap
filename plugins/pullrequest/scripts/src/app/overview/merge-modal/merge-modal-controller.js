export default MergeModalController;

MergeModalController.$inject = ["modal_instance", "shouldMergeCallback"];

function MergeModalController(modal_instance, shouldMergeCallback) {
    const self = this;

    Object.assign(self, {
        proceed,
        cancel,
    });

    function cancel() {
        modal_instance.tlp_modal.hide();
        shouldMergeCallback(false);
    }

    function proceed() {
        modal_instance.tlp_modal.hide();
        shouldMergeCallback(true);
    }
}
