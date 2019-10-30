export default CustomLicenseModalController;

CustomLicenseModalController.$inject = ["$modalInstance", "SharedPropertiesService"];

function CustomLicenseModalController($modalInstance, SharedPropertiesService) {
    const self = this;

    const license_agreement = SharedPropertiesService.getCustomLicenseAgreement();

    Object.assign(self, {
        accept: $modalInstance.close,
        decline: $modalInstance.dismiss,

        title: license_agreement.title,
        content: license_agreement.content
    });
}
