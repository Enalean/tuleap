describe('ArtifactLinksTrackerService', function() {
    var ArtifactLinksGraphRestService,
        ArtifactLinksModelService,
        ArtifactLinksTrackersList,
        ArtifactLinksTrackerService;

    beforeEach(function () {
        ArtifactLinksGraphRestService = jasmine.createSpyObj('ArtifactLinksGraphRestService', [
            'getTracker'
        ]);
        ArtifactLinksModelService = jasmine.createSpyObj('ArtifactLinksModelService', [
            'getTrackerExecutionId', 'getArtifactLinksField'
        ]);
        ArtifactLinksTrackersList = {
            trackers: {}
        };


        module('tuleap.artifact-links-graph', function ($provide) {
            $provide.value('ArtifactLinksGraphRestService', ArtifactLinksGraphRestService);
            $provide.value('ArtifactLinksModelService', ArtifactLinksModelService);
            $provide.value('ArtifactLinksTrackersList', ArtifactLinksTrackersList);
        });

        inject(function (_ArtifactLinksTrackerService_) {
            ArtifactLinksTrackerService = _ArtifactLinksTrackerService_;
        });

        installPromiseMatchers();
    });

    describe("initializeTrackers() -", function () {
        it("Given artifacts, when I initialize tracker objects then an object containing all trackers will be return", function () {
            var trackers = {
                26: {
                    id: 26,
                    item_name: "Test",
                    color: "blue"
                },
                54: {
                    id: 54,
                    item_name: "Other",
                    color: "blue"
                }
            };
            ArtifactLinksTrackersList.trackers = trackers;

            var artifacts = {
                nodes: [],
                current_node: {
                    tracker: {
                        id: 24
                    }
                }
            };
            spyOn(ArtifactLinksTrackerService, 'getTrackersIds').andReturn([
                54, 26
            ]);

            var promise = ArtifactLinksTrackerService.initializeTrackers(artifacts);

            expect(promise).toBeResolvedWith(trackers);
        });

        it("Given artifacts, when I initialize tracker objects then an object containing all trackers will be return and call Rest API for trackers which doesn't exist", function() {
            var trackers = {
                26: {
                    id: 26,
                    item_name: "Test",
                    color: "blue"
                },
                54: {
                    id: 54,
                    item_name: "Other",
                    color: "blue"
                }
            };
            ArtifactLinksTrackersList.trackers = trackers;

            var artifacts = {
                nodes: [],
                current_node: {
                    tracker: {
                        id: 1
                    }
                }
            };
            spyOn(ArtifactLinksTrackerService, 'getTrackersIds').andReturn([
                54, 26, 1
            ]);

            var promise = ArtifactLinksTrackerService.initializeTrackers(artifacts);
            expect(promise).toBeResolvedWith(trackers);
            expect(ArtifactLinksGraphRestService.getTracker).toHaveBeenCalled();
        });
    });

    describe("getTrackersIds() -", function () {
        it("Given artifacts, when I initialize tracker objects then return all tracker ids without duplicates", function () {
            var artifacts = {
                nodes: [
                    {
                        id: 8,
                        tracker: {
                            id: 70
                        },
                        values: [
                            {
                                type: 'art_link',
                                links: [
                                    { id: 10, tracker: { id: 41 } },
                                    { id: 11, tracker: { id: 41 } },
                                    { id: 12, tracker: { id: 42 } }
                                ],
                                reverse_links: [
                                    { id: 10, tracker: { id: 41 } }
                                ]
                            }
                        ]
                    }
                ],
                current_node: {
                    id: 8,
                    tracker: {
                        id: 70
                    },
                    values: [
                        {
                            type: 'art_link',
                            links: [
                                { id: 10, tracker: { id: 41 } },
                                { id: 11, tracker: { id: 41 } },
                                { id: 12, tracker: { id: 42 } }
                            ],
                            reverse_links: [
                                { id: 10, tracker: { id: 41 } }
                            ]
                        }
                    ]
                }
            };

            ArtifactLinksModelService.getArtifactLinksField.andReturn(
                {
                    type: 'art_link',
                    links: [
                        { id: 10, tracker: { id: 41 } },
                        { id: 11, tracker: { id: 41 } },
                        { id: 12, tracker: { id: 42 } }
                    ],
                    reverse_links: [
                        { id: 10, tracker: { id: 41 } }
                    ]
                }
            );

            var expected_modal_model = [41, 42, 70];

            expect(ArtifactLinksTrackerService.getTrackersIds(artifacts)).toEqual(expected_modal_model);
        });
    });

});