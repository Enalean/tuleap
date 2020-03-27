export default ErrorModalController;

ErrorModalController.$inject = ["modal_instance", "message"];

function ErrorModalController(modal_instance, message) {
    const self = this;

    Object.assign(self, {
        reloading: false,
        details: false,
        message,
    });
}
