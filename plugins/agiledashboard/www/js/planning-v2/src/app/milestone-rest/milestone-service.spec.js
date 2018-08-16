import angular from "angular";
import "angular-mocks";

import milestone_rest_module from "./milestone-rest.js";

describe("MilestoneService", function() {
    var mockBackend, MilestoneService, BacklogItemFactory;

    beforeEach(function() {
        BacklogItemFactory = jasmine.createSpyObj("BacklogItemFactory", ["augment"]);

        angular.mock.module(milestone_rest_module, function($provide) {
            $provide.value("BacklogItemFactory", BacklogItemFactory);
        });

        angular.mock.inject(function(_MilestoneService_, $httpBackend) {
            MilestoneService = _MilestoneService_;
            mockBackend = $httpBackend;
        });

        installPromiseMatchers();
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    describe("getOpenMilestones() -", function() {
        it("Given a project id, a limit of 50 items and an offset of 0, when I get the project's open milestones, then a promise will be resolved with an object containing the milestones and the X-PAGINATION-SIZE header as the total number of items", function() {
            mockBackend
                .expectGET(
                    "/api/v1/projects/12/milestones?fields=slim&limit=50&offset=0&order=desc&query=%7B%22status%22:%22open%22%7D"
                )
                .respond(
                    [
                        {
                            id: 911,
                            resources: {
                                backlog: {
                                    accept: {
                                        trackers: [{ id: 68 }]
                                    }
                                },
                                content: {
                                    accept: {
                                        trackers: [{ id: 60 }]
                                    }
                                }
                            }
                        },
                        {
                            id: 348,
                            resources: {
                                backlog: {
                                    accept: {
                                        trackers: [{ id: 26 }]
                                    }
                                },
                                content: {
                                    accept: {
                                        trackers: [{ id: 37 }]
                                    }
                                }
                            }
                        }
                    ],
                    {
                        "X-PAGINATION-SIZE": 2
                    }
                );

            var promise = MilestoneService.getOpenMilestones(12, 50, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(jasmine.objectContaining({ id: 911 }));
            expect(value.results[1]).toEqual(jasmine.objectContaining({ id: 348 }));
            expect(value.total).toEqual("2");
        });
    });
});
