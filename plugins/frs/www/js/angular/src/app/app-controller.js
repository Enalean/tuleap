angular
    .module('tuleap.frs')
    .controller('AppController', AppController);

AppController.$inject = [
    'gettextCatalog',
    'SharedPropertiesService'
];

function AppController(
    gettextCatalog,
    SharedPropertiesService
) {
    var self = this;

    self.init = init;

    function init(release, language, platform_license_info) {
        SharedPropertiesService.setProjectId(release.project.id);
        SharedPropertiesService.setRelease(angular.fromJson(release));
        SharedPropertiesService.setPlatformLicenseInfo(angular.fromJson(platform_license_info));
        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }
}
