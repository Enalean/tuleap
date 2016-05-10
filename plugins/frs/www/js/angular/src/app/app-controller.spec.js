describe("AppController -", function() {
    var gettextCatalog, AppController, SharedPropertiesService;

    beforeEach(function() {
        module('tuleap.frs');

        var $controller;

        inject(function( // eslint-disable-line angular/di
            _$controller_,
            _gettextCatalog_,
            _SharedPropertiesService_
        ) {
            $controller             = _$controller_;
            gettextCatalog          = _gettextCatalog_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "setProjectId");
        spyOn(SharedPropertiesService, "setReleaseId");
        spyOn(gettextCatalog, "setCurrentLanguage");

        AppController = $controller('AppController');
    });

    it("Given a project id, a release_id and a language, when I init the app, then the project id and release id will be set in the shared properties and the language for translations will be set", function() {
        var project_id = 197;
        var release_id = 80;
        var language   = "en";

        AppController.init(project_id, release_id, language);

        expect(SharedPropertiesService.setProjectId).toHaveBeenCalledWith(project_id);
        expect(SharedPropertiesService.setReleaseId).toHaveBeenCalledWith(release_id);
        expect(gettextCatalog.setCurrentLanguage).toHaveBeenCalledWith(language);
    });
});
