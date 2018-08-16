export default ErrorModalController;

ErrorModalController.$inject = ["$modalInstance", "message"];

function ErrorModalController($modalInstance, message) {
    const self = this;

    Object.assign(self, {
        reloading: false,
        details: false,
        message,
        ok
    });

    function ok() {
        $modalInstance.close();
    }
}
