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
        spyOn(SharedPropertiesService, "setRelease");
        spyOn(SharedPropertiesService, "setPlatformLicenseInfo");
        spyOn(gettextCatalog, "setCurrentLanguage");

        AppController = $controller('AppController');
    });

    it("Given a release object and a language, when I init the app, then the project_id and the release will be set in the shared properties and the language for translations will be set", function() {
        var release = {
            id     : 80,
            project: {
                id: 163
            }
        };
        var language   = "en";

        var platform_license_info = {
            exchange_policy_url: 'http://paraphonic.com/alphabetarian/seriousness?a=chlordan&b=unregardant#horizontalization',
            organisation_name  : 'Confidential Wrench',
            contact_email      : 'mensurableness@nonmetallic.net'
        };

        AppController.init(
            release,
            language,
            platform_license_info
        );

        expect(SharedPropertiesService.setProjectId).toHaveBeenCalledWith(release.project.id);
        expect(SharedPropertiesService.setRelease).toHaveBeenCalledWith(release);
        expect(SharedPropertiesService.setPlatformLicenseInfo).toHaveBeenCalledWith(platform_license_info);
        expect(gettextCatalog.setCurrentLanguage).toHaveBeenCalledWith(language);
    });
});
