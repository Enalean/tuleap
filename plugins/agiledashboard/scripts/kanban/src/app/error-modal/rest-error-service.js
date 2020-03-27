export default RestErrorService;

RestErrorService.$inject = ["TlpModalService"];

function RestErrorService(TlpModalService) {
    var self = this;
    self.reload = reload;

    function reload(response) {
        var message = response.status + " " + response.statusText;
        if (response.data.error) {
            message = response.data.error.code + " " + response.data.error.message;
        }

        TlpModalService.open({
            templateUrl: "error.tpl.html",
            controller: "ErrorCtrl",
            controllerAs: "error_modal",
            tlpModalOptions: { keyboard: false },
            resolve: {
                message: message,
            },
        });
    }
}
