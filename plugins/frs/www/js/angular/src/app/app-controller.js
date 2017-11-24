angular
    .module('tuleap.frs')
    .controller('AppController', AppController);

AppController.$inject = [
    '$element',
    'gettextCatalog',
    'SharedPropertiesService'
];

function AppController(
    $element,
    gettextCatalog,
    SharedPropertiesService
) {
    var self = this;

    self.init = init;
    self.init();

    function init() {
        const frs_init_data = $element[0].querySelector('.frs-init-data').dataset;

        const release = angular.fromJson(frs_init_data.release);
        SharedPropertiesService.setProjectId(release.project.id);
        SharedPropertiesService.setRelease(release);
        const platform_license_info = angular.fromJson(frs_init_data.platformLicenseInfo);
        SharedPropertiesService.setPlatformLicenseInfo(platform_license_info);
        const language = frs_init_data.language;
        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }
}
