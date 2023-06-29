import "./error-modal.tpl.html";
import controller from "./error-modal-controller.js";

export default ErrorModalService;

ErrorModalService.$inject = ["TlpModalService"];

function ErrorModalService(TlpModalService) {
    const self = this;

    Object.assign(self, {
        showErrorResponseMessage,
        showErrorMessage,
    });

    function showErrorMessage(message) {
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

    function showErrorResponseMessage(response) {
        showErrorMessage(getMessageFromResponse(response));
    }

    function getMessageFromResponse(response) {
        let message = response.status + " " + response.statusText;

        if (response.data.error) {
            message = response.data.error.code + " " + response.data.error.message;
        }

        return message;
    }
}
