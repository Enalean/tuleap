import testmanagement_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("ArtifactLinksModelService", function () {
    var gettextCatalog, ArtifactLinksModelService;

    beforeEach(function () {
        gettextCatalog = {
            getString: jest.fn(),
            setStrings: () => {},
        };

        angular.mock.module(testmanagement_module, function ($provide) {
            $provide.value("gettextCatalog", gettextCatalog);
        });

        angular.mock.inject(function (_ArtifactLinksModelService_) {
            ArtifactLinksModelService = _ArtifactLinksModelService_;
        });
    });

    it("Given an artifact structure, when I transform data then an object with nodes, links, errors and title will be return", function () {
        gettextCatalog.getString.mockReturnValue("aString");

        var artifact = {
            links: [
                {
                    id: 9,
                    uri: "testmanagement_nodes/9",
                    ref_name: "request",
                    ref_label: "Request",
                    color: "fiesta_red",
                    title: "New request",
                    url: "/plugins/tracker/?aid=9",
                    status_semantic: "closed",
                    status_label: null,
                    nature: "artifact",
                },
            ],
            reverse_links: [
                {
                    id: 6,
                    uri: "testmanagement_nodes/6",
                    ref_name: "campaign",
                    ref_label: "Validation Campaign",
                    color: "deep_blue",
                    title: "My first campaign",
                    url: "/plugins/tracker/?aid=6",
                    status_semantic: "open",
                    status_label: "Open",
                    nature: "artifact",
                },
            ],
            id: 1,
            uri: "testmanagement_nodes/1",
            ref_name: "test_def",
            ref_label: "Validation Test Definition",
            color: "sherwood_green",
            title: "My first test",
            url: "/plugins/tracker/?aid=1",
            status_semantic: "open",
            status_label: "notrun",
            nature: "artifact",
        };

        var expected_modal_model = {
            errors: [],
            graph: {
                links: [
                    {
                        source: 1,
                        target: 9,
                        type: "arrow",
                    },
                    {
                        source: 6,
                        target: 1,
                        type: "arrow",
                    },
                ],
                nodes: [
                    {
                        id: 1,
                        uri: "testmanagement_nodes/1",
                        ref_name: "test_def",
                        ref_label: "Validation Test Definition",
                        color: "sherwood_green",
                        title: "My first test",
                        url: "/plugins/tracker/?aid=1",
                        status_semantic: "open",
                        status_label: "notrun",
                        nature: "artifact",
                    },
                    {
                        id: 9,
                        uri: "testmanagement_nodes/9",
                        ref_name: "request",
                        ref_label: "Request",
                        color: "fiesta_red",
                        title: "New request",
                        url: "/plugins/tracker/?aid=9",
                        status_semantic: "closed",
                        status_label: null,
                        nature: "artifact",
                    },
                    {
                        id: 6,
                        uri: "testmanagement_nodes/6",
                        ref_name: "campaign",
                        ref_label: "Validation Campaign",
                        color: "deep_blue",
                        title: "My first campaign",
                        url: "/plugins/tracker/?aid=6",
                        status_semantic: "open",
                        status_label: "Open",
                        nature: "artifact",
                    },
                ],
            },
            title: "My first test",
        };

        expect(ArtifactLinksModelService.getGraphStructure(artifact)).toEqual(expected_modal_model);
    });

    it("Given an artifact structure without links, when I transform data then an object with nodes, links, errors and title will be return", function () {
        gettextCatalog.getString.mockReturnValue("aString");

        var artifact = {
            links: [],
            reverse_links: [],
            id: 1,
            uri: "testmanagement_nodes/1",
            ref_name: "test_def",
            ref_label: "Validation Test Definition",
            color: "sherwood_green",
            title: "My first test",
            url: "/plugins/tracker/?aid=1",
            status_semantic: "open",
            status_label: "notrun",
            nature: "artifact",
        };

        var expected_modal_model = {
            errors: [],
            graph: {
                links: [],
                nodes: [
                    {
                        id: 1,
                        uri: "testmanagement_nodes/1",
                        ref_name: "test_def",
                        ref_label: "Validation Test Definition",
                        color: "sherwood_green",
                        title: "My first test",
                        url: "/plugins/tracker/?aid=1",
                        status_semantic: "open",
                        status_label: "notrun",
                        nature: "artifact",
                    },
                ],
            },
            title: "My first test",
        };

        expect(ArtifactLinksModelService.getGraphStructure(artifact)).toEqual(expected_modal_model);
    });
});
