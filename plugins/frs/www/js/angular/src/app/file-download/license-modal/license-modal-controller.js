export default LicenseModalController;

LicenseModalController.$inject = ["$modalInstance", "SharedPropertiesService"];

function LicenseModalController($modalInstance, SharedPropertiesService) {
    const self = this;

    const platform_license_info = SharedPropertiesService.getPlatformLicenseInfo();

    Object.assign(self, {
        accept: $modalInstance.close,
        decline: $modalInstance.dismiss,

        exchange_policy_url: platform_license_info.exchange_policy_url,
        organisation_name: platform_license_info.organisation_name,
        contact_email: platform_license_info.contact_email
    });
}
