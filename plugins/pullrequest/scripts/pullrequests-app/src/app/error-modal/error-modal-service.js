import "./error-modal.tpl.html";
import controller from "./error-modal-controller.js";

export default ErrorModalService;

ErrorModalService.$inject = ["TlpModalService"];

function ErrorModalService(TlpModalService) {
    const self = this;

    Object.assign(self, {
        showError,
    });

    function showError(response) {
        const message = getMessageFromResponse(response);

        TlpModalService.open({
            templateUrl: "error-modal.tpl.html",
            controller,
            controllerAs: "error_modal",
            tlpModalOptions: {
                keyboard: false,
                backdrop: "static",
            },
            resolve: { message },
        });
    }

    function getMessageFromResponse(response) {
        let message = response.status + " " + response.statusText;

        if (response.data.error) {
            message = response.data.error.code + " " + response.data.error.message;
        }

        return message;
    }
}
