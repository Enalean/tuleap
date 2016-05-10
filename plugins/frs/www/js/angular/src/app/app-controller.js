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

    function init(project_id, release_id, language) {
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setReleaseId(release_id);
        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }
}
