angular
    .module('tuleap.frs')
    .controller('LicenseModalController', LicenseModalController);

LicenseModalController.$inject = [
    '$modalInstance',
    'lodash',
    'SharedPropertiesService'
];

function LicenseModalController(
    $modalInstance,
    _,
    SharedPropertiesService
) {
    var self = this;

    var platform_license_info = SharedPropertiesService.getPlatformLicenseInfo();

    _.extend(self, {
        accept : $modalInstance.close,
        decline: $modalInstance.dismiss,

        exchange_policy_url: platform_license_info.exchange_policy_url,
        organisation_name  : platform_license_info.organisation_name,
        contact_email      : platform_license_info.contact_email
    });
}
