describe('ArtifactLinksModelService', function() {
    var gettextCatalog,
        SharedPropertiesService,
        ArtifactLinksModelService;

    beforeEach(function() {
        gettextCatalog = jasmine.createSpyObj('gettextCatalog', [
            'getString'
        ]);
        SharedPropertiesService = jasmine.createSpyObj('SharedPropertiesService', [
            'getTrackerExecutionId'
        ]);

        module('tuleap.artifact-links-graph', function($provide) {
            $provide.value('gettextCatalog', gettextCatalog);
            $provide.value('SharedPropertiesService', SharedPropertiesService);
        });

        inject(function(_ArtifactLinksModelService_) {
            ArtifactLinksModelService = _ArtifactLinksModelService_;
        });
    });

    it("Given a artifact structure, it adds an error in modal model if there's no artifact link field", function() {
        gettextCatalog.getString.andReturn('aString');

        var execution = {
            id: 8,
            tracker: {
                id: 70
            },
            values: [
                {
                    type: 'int'
                }
            ]
        };

        var definition = {
            id: 30,
            tracker: {
                id: 70
            },
            values: [
                {
                    type: 'int'
                }
            ]
        };

        var trackers = {
            70: {
                item_name: 'test_def'
            }
        };

        var expected_modal_model = {
            errors: ['aString', 'aString'],
            graph : {
                links: [],
                nodes: []
            }
        };

        var artifacts = {
            nodes: [definition, execution],
            current_node: definition
        };

        expect(ArtifactLinksModelService.getGraphStructure(artifacts, trackers)).toEqual(expected_modal_model);
    });

    it("Given a artifact structure, it uses an empty artifact link field to get a graph model with only current artifact node", function() {
        var execution = {
            id: 8,
            tracker: {
                id: 70
            },
            values: [
                {
                    type: 'art_link',
                    links: [],
                    reverse_links: []
                }
            ]
        };

        var definition = {
            id: 30,
            tracker: {
                id: 70
            },
            values: [
                {
                    type: 'art_link',
                    links: [],
                    reverse_links: []
                }
            ]
        };

        var trackers = {
            70: {
                item_name: 'test_def',
                color_name: 'blue'
            }
        };

        var expected_modal_model = {
            errors: [],
            graph : {
                links: [],
                nodes: [
                    { id: definition.id, label: trackers[definition.tracker.id].item_name + ' #' + definition.id, color_name: 'blue'}
                ]
            }
        };

        var artifacts = {
            nodes: [definition, execution],
            current_node: definition
        };

        expect(ArtifactLinksModelService.getGraphStructure(artifacts, trackers)).toEqual(expected_modal_model);
    });

    it("Given a artifact structure, it uses the artifact link field to get a graph model", function() {
        SharedPropertiesService.getTrackerExecutionId.andReturn(41);

        var execution = {
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
                        { id: 10, tracker: { id: 41 } },
                        { id: 20, tracker: { id: 50 } },
                        { id: 21, tracker: { id: 50 } }
                    ]
                }
            ]
        };

        var definition = {
            id: 30,
            tracker: {
                id: 70
            },
            values: [
                {
                    type: 'art_link',
                    links: [
                        { id: 13, tracker: { id: 60 } },
                        { id: 14, tracker: { id: 60 } }
                    ]
                }
            ]
        };

        var trackers = {
            41: {
                item_name: 'test_def',
                color_name: 'blue'
            },
            42: {
                item_name: 'request',
                color_name: 'blue'
            },
            50: {
                item_name: 'bug',
                color_name: 'blue'
            },
            60: {
                item_name: 'test_exec',
                color_name: 'blue'
            },
            70: {
                item_name: 'story',
                color_name: 'blue'
            }
        };

        var expected_modal_model = {
            errors: [],
            graph : {
                links: [
                    { source: definition.id, target: 13, type: 'arrow' },
                    { source: definition.id, target: 14, type: 'arrow' },
                    { source: definition.id, target: 12, type: 'arrow' },
                    { source: 20, target: definition.id, type: 'arrow' },
                    { source: 21, target: definition.id, type: 'arrow' }
                ],
                nodes: [
                    { id: definition.id, label: trackers[definition.tracker.id].item_name + ' #' + definition.id, color_name: 'blue' },
                    { id: 13, label: trackers[60].item_name + ' #' + 13, color_name: 'blue' },
                    { id: 14, label: trackers[60].item_name + ' #' + 14, color_name: 'blue' },
                    { id: 12, label: trackers[42].item_name + ' #' + 12, color_name: 'blue' },
                    { id: 20, label: trackers[50].item_name + ' #' + 20, color_name: 'blue' },
                    { id: 21, label: trackers[50].item_name + ' #' + 21, color_name: 'blue' }
                ]
            }
        };

        var artifacts = {
            nodes: [definition, execution],
            current_node: definition
        };

        expect(ArtifactLinksModelService.getGraphStructure(artifacts, trackers)).toEqual(expected_modal_model);
    });
});