describe('ArtifactLinksModelService', function() {
    var gettextCatalog,
        ArtifactLinksModelService;

    beforeEach(function() {
        gettextCatalog = jasmine.createSpyObj('gettextCatalog', [
            'getString'
        ]);

        module('tuleap.artifact-links-graph', function($provide) {
            $provide.value('gettextCatalog', gettextCatalog);
        });

        inject(function(_ArtifactLinksModelService_) {
            ArtifactLinksModelService = _ArtifactLinksModelService_;
        });
    });

    it("Given an artifact structure, when I transform data then an object with nodes, links and errors will be return", function() {
        gettextCatalog.getString.andReturn('aString');

        var artifact = {
            "links": [
                {
                    "id": 9,
                    "uri": "trafficlights_nodes/9",
                    "ref_name": "request",
                    "ref_label": "Request",
                    "color": "fiesta_red",
                    "title": "New request",
                    "url": "/plugins/tracker/?aid=9",
                    "status_semantic": "closed",
                    "status_label": null,
                    "nature": "artifact"
                }
            ],
            "reverse_links": [
                {
                    "id": 6,
                    "uri": "trafficlights_nodes/6",
                    "ref_name": "campaign",
                    "ref_label": "Validation Campaign",
                    "color": "deep_blue",
                    "title": "My first campaign",
                    "url": "/plugins/tracker/?aid=6",
                    "status_semantic": "open",
                    "status_label": "Open",
                    "nature": "artifact"
                }
            ],
            "id": 1,
            "uri": "trafficlights_nodes/1",
            "ref_name": "test_def",
            "ref_label": "Validation Test Definition",
            "color": "sherwood_green",
            "title": "My first test",
            "url": "/plugins/tracker/?aid=1",
            "status_semantic": "open",
            "status_label": "notrun",
            "nature": "artifact"
        };

        var expected_modal_model = {
            errors: [],
            graph : {
                links: [
                    {
                        source: 1,
                        target: 9,
                        type: "arrow"
                    },
                    {
                        source: 6,
                        target: 1,
                        type: "arrow"
                    }
                ],
                nodes: [
                    {
                        "id": 1,
                        "uri": "trafficlights_nodes/1",
                        "ref_name": "test_def",
                        "ref_label": "Validation Test Definition",
                        "color": "sherwood_green",
                        "title": "My first test",
                        "url": "/plugins/tracker/?aid=1",
                        "status_semantic": "open",
                        "status_label": "notrun",
                        "nature": "artifact"
                    },
                    {
                        "id": 9,
                        "uri": "trafficlights_nodes/9",
                        "ref_name": "request",
                        "ref_label": "Request",
                        "color": "fiesta_red",
                        "title": "New request",
                        "url": "/plugins/tracker/?aid=9",
                        "status_semantic": "closed",
                        "status_label": null,
                        "nature": "artifact"
                    },
                    {
                        "id": 6,
                        "uri": "trafficlights_nodes/6",
                        "ref_name": "campaign",
                        "ref_label": "Validation Campaign",
                        "color": "deep_blue",
                        "title": "My first campaign",
                        "url": "/plugins/tracker/?aid=6",
                        "status_semantic": "open",
                        "status_label": "Open",
                        "nature": "artifact"
                    }
                ]
            }
        };

        expect(ArtifactLinksModelService.getGraphStructure(artifact)).toEqual(expected_modal_model);
    });
});
