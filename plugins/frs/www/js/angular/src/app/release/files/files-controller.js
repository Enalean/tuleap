export default FilesController;

FilesController.$inject = ["SharedPropertiesService"];

function FilesController(SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        release: SharedPropertiesService.getRelease(),
        custom_license_agreement: SharedPropertiesService.getCustomLicenseAgreement()
    });
}
