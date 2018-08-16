import angular from "angular";
import tuleap_frs_module from "tuleap-frs-module";

import "angular-mocks";

describe("ReleaseRestService -", function() {
    var $q, $httpBackend, ReleaseRestService, RestErrorService;

    beforeEach(function() {
        angular.mock.module(tuleap_frs_module);

        angular.mock.inject(function(
            _$httpBackend_,
            _$q_,
            _ReleaseRestService_,
            _RestErrorService_
        ) {
            $httpBackend = _$httpBackend_;
            $q = _$q_;
            ReleaseRestService = _ReleaseRestService_;
            RestErrorService = _RestErrorService_;
        });

        spyOn(RestErrorService, "setError");

        installPromiseMatchers();
    });

    afterEach(function() {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getReleaseLinkNatures() -", function() {
        it("Given an artifact id, when I get the link natures of an artifact, then a GET request will be sent to Tuleap and an array of link nature objects will be returned", function() {
            var natures = [
                {
                    shortname: "_is_child",
                    direction: "forward",
                    label: "Is Child",
                    uri: "feminity/unman?a=taysaam&b=hebdomadally#downstreet"
                },
                {
                    shortname: "",
                    direction: "reverse",
                    label: "",
                    uri: "subcentral/updraw?a=enmoss&b=monoicous#masterlily"
                }
            ];

            $httpBackend.expectGET("/api/v1/artifacts/752/links").respond(
                angular.toJson({
                    natures: natures
                })
            );

            var promise = ReleaseRestService.getReleaseLinkNatures(752);
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(natures);
        });

        it("when the server responds with an error, then the error will be set in the error service", function() {
            $httpBackend.expectGET("/api/v1/artifacts/286/links").respond(403, "Forbidden");

            var promise = ReleaseRestService.getReleaseLinkNatures(286);
            $httpBackend.flush();

            expect(RestErrorService.setError).toHaveBeenCalledWith({
                code: 403,
                message: "Forbidden"
            });
            expect(promise).toBeRejected();
        });
    });

    describe("getLinkedArtifacts() -", function() {
        it("Given a URI, a limit and offset, when I get the linked artifacts at the URI, then a GET request will be sent to Tuleap and an object containing the total number of artifacts and a collection of artifacts will be returned", function() {
            var total_linked_artifacts = 21;
            var headers = {
                "X-Pagination-Size": total_linked_artifacts
            };

            var linked_artifacts = [
                {
                    id: 459
                },
                {
                    id: 194
                }
            ];

            $httpBackend
                .expectGET(
                    "/api/v1/artifacts/392/linked_artifacts?nature=elflock&direction=forward&limit=50&offset=0"
                )
                .respond(
                    angular.toJson({
                        collection: linked_artifacts
                    }),
                    headers
                );

            var uri = "artifacts/392/linked_artifacts?nature=elflock&direction=forward";

            var promise = ReleaseRestService.getLinkedArtifacts(uri, 50, 0);
            $httpBackend.flush();

            expect(promise).toBeResolvedWith({
                results: linked_artifacts,
                total: total_linked_artifacts
            });
        });

        it("when the server responds with an error, then the error will be set in the error service", function() {
            $httpBackend
                .expectGET(
                    "/api/v1/artifacts/676/linked_artifacts?nature=elflock&direction=forward&limit=50&offset=0"
                )
                .respond(403, "Forbidden");

            var uri = "artifacts/676/linked_artifacts?nature=elflock&direction=forward";

            var promise = ReleaseRestService.getLinkedArtifacts(uri, 50, 0);
            $httpBackend.flush();

            expect(RestErrorService.setError).toHaveBeenCalledWith({
                code: 403,
                message: "Forbidden"
            });
            expect(promise).toBeRejected();
        });
    });

    describe("getAllLinkedArtifacts() -", function() {
        it("Given a URI and a progress callback, given a pagination limit of 2 and given there were 4 linked artifacts, when I get the linked artifacts at the URI, then two requests will be sent to Tuleap , for each resolved request the progress callback will be called with the results and a promise will be resolved with a single array containing all results", function() {
            var first_artifacts = [
                {
                    id: 153
                },
                {
                    id: 356
                }
            ];

            var second_artifacts = [
                {
                    id: 433
                },
                {
                    id: 422
                }
            ];
            spyOn(ReleaseRestService, "getLinkedArtifacts").and.callFake(function(
                uri,
                limit,
                offset
            ) {
                if (offset === 0) {
                    return $q.when({
                        results: first_artifacts,
                        total: 4
                    });
                } else if (offset === 2) {
                    return $q.when({
                        results: second_artifacts,
                        total: 4
                    });
                }
            });

            ReleaseRestService.linked_artifacts_pagination_limit = 2;
            var uri = "artifacts/417/linked_artifacts?nature=neurokeratin&direction=reverse";
            var progress_callback = jasmine.createSpy("progress_callback");
            var promise = ReleaseRestService.getAllLinkedArtifacts(uri, progress_callback);

            var all_artifacts = first_artifacts.concat(second_artifacts);

            expect(promise).toBeResolvedWith(all_artifacts);
            expect(progress_callback).toHaveBeenCalledWith(first_artifacts);
            expect(progress_callback).toHaveBeenCalledWith(second_artifacts);
            expect(ReleaseRestService.getLinkedArtifacts).toHaveBeenCalledWith(uri, 2, 0);
            expect(ReleaseRestService.getLinkedArtifacts).toHaveBeenCalledWith(uri, 2, 2);
            expect(ReleaseRestService.getLinkedArtifacts.calls.count()).toBe(2);
        });
    });
});
