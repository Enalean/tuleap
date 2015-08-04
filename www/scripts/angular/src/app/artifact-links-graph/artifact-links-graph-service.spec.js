describe('ArtifactLinksGraphService', function() {
    var $modal, gettextCatalog, ArtifactLinksGraphRestService, ArtifactLinksGraphService;

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

            $provide.value('gettextCatalog', gettextCatalog);
            $provide.value('$modal', $modal);
            $provide.value('ArtifactLinksGraphRestService', ArtifactLinksGraphRestService);
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

        var artifact = {
            id: 8,
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

        expect(ArtifactLinksGraphService.getGraphStructure(artifact)).toEqual(expected_modal_model);
    });

    it("Given a artifact structure, it uses an empty artifact link field to get a graph model with only current artifact node", function() {
        var artifact = {
            id: 8,
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
                    { id: artifact.id, label: '#' + artifact.id }
                ]
            }
        };

        expect(ArtifactLinksGraphService.getGraphStructure(artifact)).toEqual(expected_modal_model);
    });

    it("Given a artifact structure, it uses the artifact link field to get a graph model", function() {
        var artifact = {
            id: 8,
            values: [
                {
                    type: 'art_link',
                    links: [
                        { id: 10 },
                        { id: 11 },
                        { id: 12 }
                    ],
                    reverse_links: [
                        { id: 10 },
                        { id: 20 },
                        { id: 21 }
                    ]
                }
            ]
        };

        var expected_modal_model = {
            errors: [],
            graph : {
                links: [
                    { source: artifact.id, target: 10, type: 'arrow' },
                    { source: artifact.id, target: 11, type: 'arrow' },
                    { source: artifact.id, target: 12, type: 'arrow' },
                    { source: 10, target: artifact.id, type: 'arrow' },
                    { source: 20, target: artifact.id, type: 'arrow' },
                    { source: 21, target: artifact.id, type: 'arrow' }
                ],
                nodes: [
                    { id: artifact.id, label: '#' + artifact.id },
                    { id: 10, label: '#' + 10 },
                    { id: 11, label: '#' + 11 },
                    { id: 12, label: '#' + 12 },
                    { id: 20, label: '#' + 20 },
                    { id: 21, label: '#' + 21 }
                ]
            }
        };

        expect(ArtifactLinksGraphService.getGraphStructure(artifact)).toEqual(expected_modal_model);
    });
});