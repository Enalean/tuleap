import "./edit-modal.tpl.html";
import controller from "./edit-modal-controller.js";

export default EditModalService;

EditModalService.$inject = ["TlpModalService"];

function EditModalService(TlpModalService) {
    const self = this;

    Object.assign(self, {
        showEditModal,
    });

    function showEditModal(pullrequest, on_success_callback) {
        TlpModalService.open({
            templateUrl: "edit-modal.tpl.html",
            controller,
            controllerAs: "edit_modal",
            tlpModalOptions: {
                keyboard: true,
                backdrop: "static",
            },
            resolve: { pullrequest, on_success_callback },
        });
    }
}
