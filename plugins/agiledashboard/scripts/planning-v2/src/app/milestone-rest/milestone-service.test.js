import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../../tests/jest/angular-promise-wrapper.js";

describe("MilestoneService", () => {
    let mockBackend, wrapPromise, MilestoneService, BacklogItemFactory;

    beforeEach(() => {
        BacklogItemFactory = { augment: jest.fn() };

        angular.mock.module(planning_module, function ($provide) {
            $provide.value("BacklogItemFactory", BacklogItemFactory);
        });

        let $rootScope;
        angular.mock.inject(function (_$rootScope_, _MilestoneService_, $httpBackend) {
            $rootScope = _$rootScope_;
            MilestoneService = _MilestoneService_;
            mockBackend = $httpBackend;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(function () {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    describe("getOpenMilestones()", () => {
        it(`Given a project id, a limit of 50 items and an offset of 0,
            when I get the project's open milestones,
            then a promise will be resolved with an object containing the milestones
            and the X-PAGINATION-SIZE header as the total number of items`, async () => {
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
                                        trackers: [{ id: 68 }],
                                    },
                                },
                                content: {
                                    accept: {
                                        trackers: [{ id: 60 }],
                                    },
                                },
                            },
                        },
                        {
                            id: 348,
                            resources: {
                                backlog: {
                                    accept: {
                                        trackers: [{ id: 26 }],
                                    },
                                },
                                content: {
                                    accept: {
                                        trackers: [{ id: 37 }],
                                    },
                                },
                            },
                        },
                    ],
                    {
                        "X-PAGINATION-SIZE": 2,
                    }
                );

            var promise = MilestoneService.getOpenMilestones(12, 50, 0);
            mockBackend.flush();

            await wrapPromise(promise);
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(expect.objectContaining({ id: 911 }));
            expect(value.results[1]).toEqual(expect.objectContaining({ id: 348 }));
            expect(value.total).toEqual("2");
        });
    });
});
