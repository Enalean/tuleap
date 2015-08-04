describe('ArtifactLinksGraphService', function() {
    var $modal, gettextCatalog, ArtifactLinksGraphRestService, ArtifactLinksGraphService, SharedPropertiesService;

    beforeEach(function() {
        module('tuleap.artifact-links-graph', function($provide) {
            gettextCatalog = jasmine.createSpyObj('gettextCatalog', [
                'getString'
            ]);
            $modal = jasmine.createSpyObj('$modal', [
                'open'
            ]);
            ArtifactLinksGraphRestService = jasmine.createSpyObj('ArtifactLinksGraphRestService', [
                'getArtifact'
            ]);
            SharedPropertiesService = jasmine.createSpyObj('SharedPropertiesService', [
                'getTrackerExecutionId'
            ]);

            $provide.value('gettextCatalog', gettextCatalog);
            $provide.value('$modal', $modal);
            $provide.value('ArtifactLinksGraphRestService', ArtifactLinksGraphRestService);
            $provide.value('SharedPropertiesService', SharedPropertiesService);
        });

        inject(function(
            _$modal_,
            _gettextCatalog_,
            _ArtifactLinksGraphService_
        ) {
            $modal = _$modal_;
            gettextCatalog = _gettextCatalog_;
            ArtifactLinksGraphService = _ArtifactLinksGraphService_;
        });
    });

    it("Given a artifact structure, it adds an error in modal model if there's no artifact link field", function() {
        gettextCatalog.getString.andReturn('aString');

        var execution = {
            id: 8,
            values: [
                {
                    type: 'int'
                }
            ]
        };

        var definition = {
            id: 30,
            values: [
                {
                    type: 'int'
                }
            ]
        };

        var expected_modal_model = {
            errors: ['aString'],
            graph : {
                links: [],
                nodes: []
            }
        };

        expect(ArtifactLinksGraphService.getGraphStructure(execution, definition)).toEqual(expected_modal_model);
    });

    it("Given a artifact structure, it uses an empty artifact link field to get a graph model with only current artifact node", function() {
        var execution = {
            id: 8,
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
            values: [
                {
                    type: 'art_link',
                    links: [],
                    reverse_links: []
                }
            ]
        };

        var expected_modal_model = {
            errors: [],
            graph : {
                links: [],
                nodes: [
                    { id: definition.id, label: '#' + definition.id }
                ]
            }
        };

        expect(ArtifactLinksGraphService.getGraphStructure(execution, definition)).toEqual(expected_modal_model);
    });

    it("Given a artifact structure, it uses the artifact link field to get a graph model", function() {
        SharedPropertiesService.getTrackerExecutionId.andReturn(41);

        var execution = {
            id: 8,
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

        var expected_modal_model = {
            errors: [],
            graph : {
                links: [
                    { source: definition.id, target: 12, type: 'arrow' },
                    { source: 20, target: definition.id, type: 'arrow' },
                    { source: 21, target: definition.id, type: 'arrow' },
                    { source: definition.id, target: 13, type: 'arrow' },
                    { source: definition.id, target: 14, type: 'arrow' }
                ],
                nodes: [
                    { id: definition.id, label: '#' + definition.id },
                    { id: 12, label: '#' + 12 },
                    { id: 20, label: '#' + 20 },
                    { id: 21, label: '#' + 21 },
                    { id: 13, label: '#' + 13 },
                    { id: 14, label: '#' + 14 }
                ]
            }
        };

        expect(ArtifactLinksGraphService.getGraphStructure(execution, definition)).toEqual(expected_modal_model);
    });
});