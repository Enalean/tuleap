describe("ReleaseController -", function() {
    var $q, $rootScope, $controller,
        ReleaseController, SharedPropertiesService, ReleaseRestService;

    beforeEach(function() {
        module('tuleap.frs');

        inject(function( // eslint-disable-line angular/di
            _$q_,
            _$rootScope_,
            _$controller_,
            _SharedPropertiesService_,
            _ReleaseRestService_
        ) {
            $controller             = _$controller_;
            $q                      = _$q_;
            $rootScope              = _$rootScope_;
            ReleaseRestService      = _ReleaseRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getProjectId");
        spyOn(SharedPropertiesService, "getReleaseId");
        spyOn(ReleaseRestService, "getRelease");
    });

    it("Given that SharedPropertiesService had been correctly initialized, when I initialize the release controller then the release will be retrieved using the rest service and will be bound to the controller", function() {
        var project_id = 194;
        var release_id = 47;
        var release    = {
            id  : release_id,
            name: "v0.1.5 priceable-disconnectedness"
        };

        var release_request = $q(function(resolve) {
            resolve(release);
        });

        SharedPropertiesService.getProjectId.and.returnValue(project_id);
        SharedPropertiesService.getReleaseId.and.returnValue(release_id);
        ReleaseRestService.getRelease.and.returnValue(release_request);

        ReleaseController = $controller('ReleaseController');
        $rootScope.$apply();

        expect(SharedPropertiesService.getProjectId).toHaveBeenCalled();
        expect(SharedPropertiesService.getReleaseId).toHaveBeenCalled();
        expect(ReleaseRestService.getRelease).toHaveBeenCalledWith(release_id);
        expect(ReleaseController.project_id).toEqual(project_id);
        expect(ReleaseController.release).toEqual(release);
    });
});
