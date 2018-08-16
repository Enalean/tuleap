import angular from "angular";
import tuleap_frs_module from "tuleap-frs-module";
import linked_artifacts_controller from "./linked-artifacts-controller.js";

import "angular-mocks";

describe("LinkedArtifactsController -", function() {
    var $q,
        $controller,
        $rootScope,
        LinkedArtifactsController,
        ReleaseRestService,
        SharedPropertiesService;

    beforeEach(function() {
        angular.mock.module(tuleap_frs_module);

        angular.mock.inject(function(
            _$q_,
            _$rootScope_,
            _$controller_,
            _SharedPropertiesService_,
            _ReleaseRestService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            ReleaseRestService = _ReleaseRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "getRelease");
        spyOn(ReleaseRestService, "getReleaseLinkNatures");
        spyOn(ReleaseRestService, "getAllLinkedArtifacts");
    });

    describe("init() -", function() {
        it("Given that SharedProperties had been correctly initialized, when I initialize the linked artifacts controller then the release's link natures will be retrieved and for each nature the linked artifacts will be retrieved", function() {
            var release = {
                id: 24,
                artifact: {
                    id: 117
                }
            };
            SharedPropertiesService.getRelease.and.returnValue(release);

            var nature_is_child = {
                shortname: "_is_child",
                direction: "forward",
                label: "Children",
                uri: "/futurize/czarism?a=bason&b=polystome"
            };
            var nature_fixed_in = {
                shortname: "fixed_in",
                direction: "reverse",
                label: "Fixed In",
                uri: "/scaphocephalus/grieved?a=junketing&b=drowner"
            };
            ReleaseRestService.getReleaseLinkNatures.and.returnValue(
                $q.when([nature_is_child, nature_fixed_in])
            );

            var is_child_artifacts = [
                {
                    id: 114
                },
                {
                    id: 248
                }
            ];
            var fixed_in_artifacts = [
                {
                    id: 329
                },
                {
                    id: 292
                }
            ];
            ReleaseRestService.getAllLinkedArtifacts.and.callFake(function(uri, callback) {
                if (uri === nature_is_child.uri) {
                    callback(is_child_artifacts);
                    return $q.when(is_child_artifacts);
                } else if (uri === nature_fixed_in.uri) {
                    callback(fixed_in_artifacts);
                    return $q.when(fixed_in_artifacts);
                }
            });

            LinkedArtifactsController = $controller(linked_artifacts_controller);
            expect(LinkedArtifactsController.loading_natures).toBeTruthy();
            $rootScope.$apply();

            expect(ReleaseRestService.getReleaseLinkNatures).toHaveBeenCalledWith(
                release.artifact.id
            );
            expect(ReleaseRestService.getAllLinkedArtifacts).toHaveBeenCalledWith(
                nature_is_child.uri,
                jasmine.any(Function)
            );
            expect(ReleaseRestService.getAllLinkedArtifacts).toHaveBeenCalledWith(
                nature_fixed_in.uri,
                jasmine.any(Function)
            );
            expect(ReleaseRestService.getAllLinkedArtifacts.calls.count()).toBe(2);
            expect(LinkedArtifactsController.natures[0]).toEqual(
                jasmine.objectContaining(nature_is_child)
            );
            expect(LinkedArtifactsController.natures[0].linked_artifacts).toEqual(
                is_child_artifacts
            );
            expect(LinkedArtifactsController.natures[1]).toEqual(
                jasmine.objectContaining(nature_fixed_in)
            );
            expect(LinkedArtifactsController.natures[1].linked_artifacts).toEqual(
                fixed_in_artifacts
            );
            expect(LinkedArtifactsController.loading_natures).toBeFalsy();
        });

        it("Given that I had links and reverse links with no nature, when I initialize the linked artifacts controller then their linked artifacts will intentionally not be retrieved", function() {
            var release = {
                id: 20,
                artifact: {
                    id: 375
                }
            };
            SharedPropertiesService.getRelease.and.returnValue(release);

            var no_nature_forward = {
                shortname: "",
                direction: "forward",
                label: "",
                uri: "/besigh/medino?a=sialic&b=tylerize"
            };

            var no_nature_reverse = {
                shortname: "",
                direction: "reverse",
                label: "",
                uri: "/odinitic/prophase?a=nunlet&b=sabino"
            };
            ReleaseRestService.getReleaseLinkNatures.and.returnValue(
                $q.when([no_nature_forward, no_nature_reverse])
            );
            ReleaseRestService.getAllLinkedArtifacts.and.returnValue($q.when());

            LinkedArtifactsController = $controller(linked_artifacts_controller);
            $rootScope.$apply();

            expect(LinkedArtifactsController.natures).toEqual([]);
            expect(ReleaseRestService.getAllLinkedArtifacts).not.toHaveBeenCalled();
        });
    });
});
