import "./merge-modal.tpl.html";

export default MergeModalService;

MergeModalService.$inject = ["$modal"];

function MergeModalService($modal) {
    const self = this;

    Object.assign(self, {
        showMergeModal
    });

    function showMergeModal() {
        const modalInstance = $modal.open({
            templateUrl: "merge-modal.tpl.html",
            controller: "MergeModalController as merge_modal"
        });
        return modalInstance.result;
    }
}
