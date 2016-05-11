describe("ReleaseController -", function() {
    var $controller,
        ReleaseController,
        SharedPropertiesService;

    beforeEach(function() {
        module('tuleap.frs');

        inject(function( // eslint-disable-line angular/di
            _$controller_,
            _SharedPropertiesService_
        ) {
            $controller             = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getProjectId");
        spyOn(SharedPropertiesService, "getRelease");
    });

    it("Given that SharedPropertiesService had been correctly initialized, when I initialize the release controller then the release will be retrieved from the SharedPropertiesService and will be bound to the controller", function() {
        var project_id = 150;
        var release    = {
            id     : 44,
            name   : "v0.1.5 priceable-disconnectedness",
            project: {
                id: project_id
            }
        };

        SharedPropertiesService.getProjectId.and.returnValue(project_id);
        SharedPropertiesService.getRelease.and.returnValue(release);

        ReleaseController = $controller('ReleaseController');

        expect(SharedPropertiesService.getProjectId).toHaveBeenCalled();
        expect(SharedPropertiesService.getRelease).toHaveBeenCalled();
        expect(ReleaseController.project_id).toEqual(project_id);
        expect(ReleaseController.release).toEqual(release);
    });
});
