import "./merge-modal.tpl.html";
import controller from "./merge-modal-controller.js";

export default MergeModalService;

MergeModalService.$inject = ["TlpModalService"];

function MergeModalService(TlpModalService) {
    const self = this;

    Object.assign(self, {
        showMergeModal,
    });

    function showMergeModal() {
        return new Promise((resolve) => {
            TlpModalService.open({
                templateUrl: "merge-modal.tpl.html",
                controller,
                controllerAs: "merge_modal",
                tlpModalOptions: {
                    keyboard: false,
                    backdrop: "static",
                },
                resolve: {
                    shouldMergeCallback: (should_merge) => {
                        resolve(should_merge);
                    },
                },
            });
        }).then((should_merge) => should_merge);
    }
}
