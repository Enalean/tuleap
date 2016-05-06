angular
    .module('tuleap.frs')
    .controller('AppController', AppController);

AppController.$inject = [
    'gettextCatalog'
];

function AppController(
    gettextCatalog
) {
    var self = this;

    self.init = init;

    function init(release_id, language) {
        initLocale(language);

        self.itWorks = 'IT WORKS ! release_id = ' + release_id;
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }
}
