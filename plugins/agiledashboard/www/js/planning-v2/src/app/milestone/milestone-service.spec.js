describe("MilestoneService", function() {
    var $q, mockBackend, MilestoneService, deferred;

    beforeEach(function() {
        BacklogItemFactory = jasmine.createSpyObj("BacklogItemFactory", [
            "augment"
        ]);

        module('milestone', function($provide) {
            $provide.value('BacklogItemFactory', BacklogItemFactory);
        });

        inject(function(_$q_, _MilestoneService_, $httpBackend) {
            $q = _$q_;
            MilestoneService = _MilestoneService_;
            mockBackend = $httpBackend;
        });

        installPromiseMatchers();

        deferred = $q.defer();
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    describe("getOpenMilestones() -", function() {
        it("Given a project id, a limit of 50 items and an offset of 0, when I get the project's open milestones, then a promise will be resolved with an object containing the milestones and the X-PAGINATION-SIZE header as the total number of items", function() {
            mockBackend.expectGET('/api/v1/projects/12/milestones?limit=50&offset=0&order=desc&query=%7B%22status%22:%22open%22%7D').respond([
                {
                    id: 911,
                    resources: {
                        backlog: {
                            accept: {
                                trackers: [
                                    { id: 68 }
                                ]
                            }
                        },
                        content: {
                            accept: {
                                trackers: [
                                    { id: 60 }
                                ]
                            }
                        }
                    }
                }, {
                    id: 348,
                    resources: {
                        backlog: {
                            accept: {
                                trackers: [
                                    { id: 26 }
                                ]
                            }
                        },
                        content: {
                            accept: {
                                trackers: [
                                    { id: 37 }
                                ]
                            }
                        }
                    }
                }
            ], {
                'X-PAGINATION-SIZE': 2
            });

            var promise = MilestoneService.getOpenMilestones(12, 50, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var value = promise.$$state.value;
            expect(value.results[0]).toEqual(jasmine.objectContaining(
                { id: 911 }
            ));
            expect(value.results[1]).toEqual(jasmine.objectContaining(
                { id: 348 }
            ));
            expect(value.total).toEqual('2');
        });
    });
});
