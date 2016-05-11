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

    function init(release, language) {
        SharedPropertiesService.setProjectId(release.project.id);
        SharedPropertiesService.setRelease(release);
        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }
}
