import angular from "angular";
import tuleap_frs_module from "tuleap-frs-module";
import release_controller from "./release-controller.js";

import "angular-mocks";

describe("ReleaseController -", function() {
    var $q, $controller, $rootScope, ReleaseController, ReleaseRestService, SharedPropertiesService;

    beforeEach(function() {
        angular.mock.module(tuleap_frs_module);

        angular.mock.inject(function(
            _$q_,
            _$rootScope_,
            _$controller_,
            _ReleaseRestService_,
            _SharedPropertiesService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            ReleaseRestService = _ReleaseRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getProjectId");
        spyOn(SharedPropertiesService, "getRelease");
        spyOn(ReleaseRestService, "getMilestone");
    });

    describe("init() -", function() {
        it("Given that SharedPropertiesService had been correctly initialized, when I initialize the release controller then the release will be retrieved from the SharedPropertiesService and will be bound to the controller", function() {
            var project_id = 150;
            var release = {
                id: 44,
                name: "v0.1.5 priceable-disconnectedness",
                project: {
                    id: project_id
                },
                artifact: {
                    id: 230
                }
            };

            SharedPropertiesService.getProjectId.and.returnValue(project_id);
            SharedPropertiesService.getRelease.and.returnValue(release);
            ReleaseRestService.getMilestone.and.returnValue($q.when());

            ReleaseController = $controller(release_controller);

            expect(SharedPropertiesService.getProjectId).toHaveBeenCalled();
            expect(SharedPropertiesService.getRelease).toHaveBeenCalled();
            expect(ReleaseController.project_id).toEqual(project_id);
            expect(ReleaseController.release).toEqual(release);
            expect(ReleaseController.error_no_release_artifact).toBeFalsy();
        });

        it("Given that no artifact had been bound to the FRS release, when I init the release controller then an error boolean will be set to true", function() {
            var release = {
                id: 92,
                artifact: null
            };

            SharedPropertiesService.getRelease.and.returnValue(release);

            ReleaseController = $controller(release_controller);

            expect(ReleaseController.error_no_release_artifact).toBeTruthy();
        });

        it("Given that no artifact had been bound to the FRS release, when I init the release controller then the milestone property is null", function() {
            var release = {
                id: 92,
                artifact: null
            };

            SharedPropertiesService.getRelease.and.returnValue(release);

            ReleaseController = $controller(release_controller);

            expect(ReleaseController.milestone).toBeFalsy();
        });

        it("Given that the artifact bound to the FRS release is also a milestone, when I init the release controller then the milestone property is fed with the Milestone object", function() {
            var release = {
                id: 44,
                artifact: {
                    id: 230
                }
            };

            var milestone = {
                id: 230
            };

            SharedPropertiesService.getRelease.and.returnValue(release);
            ReleaseRestService.getMilestone.and.returnValue($q.when(milestone));

            ReleaseController = $controller(release_controller);
            $rootScope.$apply();

            expect(ReleaseController.milestone.id).toEqual(230);
        });
    });
});
