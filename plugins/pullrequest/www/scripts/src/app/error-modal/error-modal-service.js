import "./error-modal.tpl.html";

export default ErrorModalService;

ErrorModalService.$inject = ["$modal"];

function ErrorModalService($modal) {
    const self = this;

    Object.assign(self, {
        showError
    });

    function showError(response) {
        $modal.open({
            keyboard: false,
            backdrop: "static",
            templateUrl: "error-modal.tpl.html",
            controller: "ErrorModalController as error_modal",
            resolve: {
                message: () => {
                    let message = response.status + " " + response.statusText;

                    if (response.data.error) {
                        message = response.data.error.code + " " + response.data.error.message;
                    }

                    return message;
                }
            }
        });
    }
}
