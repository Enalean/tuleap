import planning_module from "../app.js";

import angular from "angular";
import "angular-mocks";
import moment from "moment";

describe("InPropertiesItemFilter", () => {
    var in_properties_filter,
        list = [
            {
                label: "Riri",
                id: "nephew",
                card_fields: [],
            },
            {
                label: "Fifi",
                id: "nephew",
                card_fields: [],
                parent: {
                    id: "autoplagiarism",
                    label: "norleucine",
                    tracker: {
                        project: {
                            id: 103,
                            label: "parent_project",
                        },
                    },
                },
                project: {
                    id: 103,
                },
            },
            {
                label: "Loulou",
                id: "nephew",
                card_fields: [],
                initial_effort: 11,
            },
            {
                label: "Donald",
                id: "uncle",
                card_fields: [],
                internal_property: "has nephews",
            },
            {
                label: "Donald & Daisy",
                id: "significant others",
                card_fields: [],
            },
            {
                label: "Della",
                id: "sister",
                card_fields: [],
                parent: {
                    id: "some_id",
                    label: "isoleucine",
                    tracker: {
                        project: {
                            id: 103,
                            label: "parent_project",
                        },
                    },
                },
                project: {
                    id: 102,
                },
            },
        ];

    beforeEach(() => {
        angular.mock.module(planning_module);

        var $filter;
        angular.mock.inject(function (_$filter_) {
            $filter = _$filter_;
        });

        in_properties_filter = $filter("InPropertiesFilter");
        (moment.locale || moment.lang)("en");
    });

    it("has a InPropertiesFilter filter", function () {
        expect(in_properties_filter).not.toBeNull();
    });

    it("filters on label", function () {
        expect(in_properties_filter(list, "Donald")).toContainEqual({
            label: "Donald",
            id: "uncle",
            card_fields: [],
            internal_property: "has nephews",
        });
        expect(in_properties_filter(list, "Donald")).toContainEqual({
            label: "Donald & Daisy",
            id: "significant others",
            card_fields: [],
        });
        expect(in_properties_filter(list, "Donald")).not.toContainEqual({
            label: "Riri",
            id: "nephew",
            card_fields: [],
        });
    });

    it("is case insensitive", function () {
        expect(in_properties_filter(list, "RIRI")).toContainEqual({
            label: "Riri",
            id: "nephew",
            card_fields: [],
        });
    });

    it("filters on id", function () {
        expect(in_properties_filter(list, "nephew")).toContainEqual({
            label: "Riri",
            id: "nephew",
            card_fields: [],
        });
        expect(in_properties_filter(list, "nephew")).toContainEqual({
            label: "Fifi",
            id: "nephew",
            card_fields: [],
            parent: {
                id: "autoplagiarism",
                label: "norleucine",
                tracker: {
                    project: {
                        id: 103,
                        label: "parent_project",
                    },
                },
            },
            project: {
                id: 103,
            },
        });
        expect(in_properties_filter(list, "nephew")).toContainEqual({
            label: "Loulou",
            id: "nephew",
            card_fields: [],
            initial_effort: 11,
        });
        expect(in_properties_filter(list, "nephew")).not.toContainEqual({
            label: "Donald & Daisy",
            id: "significant others",
            card_fields: [],
        });
    });

    it("filters on the initial_effort", function () {
        expect(in_properties_filter(list, "11")).toEqual([
            {
                label: "Loulou",
                id: "nephew",
                card_fields: [],
                initial_effort: 11,
            },
        ]);
    });

    it("filters on the item's parent's label property", function () {
        expect(in_properties_filter(list, "norleucine")).toEqual([
            {
                label: "Fifi",
                id: "nephew",
                card_fields: [],
                parent: {
                    id: "autoplagiarism",
                    label: "norleucine",
                    tracker: {
                        project: {
                            id: 103,
                            label: "parent_project",
                        },
                    },
                },
                project: {
                    id: 103,
                },
            },
        ]);
    });

    it("filters on the item's parent's project's label property when it's from another project", function () {
        expect(in_properties_filter(list, "parent_project")).toEqual([
            {
                label: "Della",
                id: "sister",
                card_fields: [],
                parent: {
                    id: "some_id",
                    label: "isoleucine",
                    tracker: {
                        project: {
                            id: 103,
                            label: "parent_project",
                        },
                    },
                },
                project: {
                    id: 102,
                },
            },
        ]);
    });

    it("does not filter on the item's parent's other properties", function () {
        expect(in_properties_filter(list, "autoplagiarism")).not.toContain({
            label: "Fifi",
            id: "nephew",
            card_fields: [],
            parent: {
                id: "autoplagiarism",
                label: "norleucine",
            },
        });
    });

    it("does not filter on private properties", function () {
        expect(in_properties_filter(list, "nephew")).not.toContain({
            label: "Donald",
            id: "uncle",
            card_fields: [],
            internal_property: "has nephews",
        });
    });

    it("filters on both label and id", function () {
        expect(in_properties_filter(list, "nephew riri")).toContainEqual({
            label: "Riri",
            id: "nephew",
            card_fields: [],
        });
    });

    it("returns items that match all criteria", function () {
        expect(in_properties_filter(list, "donald daisy")).toContainEqual({
            label: "Donald & Daisy",
            id: "significant others",
            card_fields: [],
        });
        expect(in_properties_filter(list, "donald daisy")).not.toContainEqual({
            label: "Donald",
            id: "uncle",
            card_fields: [],
            internal_property: "has nephews",
        });
        expect(in_properties_filter(list, "daisy donald")).toContainEqual({
            label: "Donald & Daisy",
            id: "significant others",
            card_fields: [],
        });
        expect(in_properties_filter(list, "daisy donald")).not.toContainEqual({
            label: "Donald",
            id: "uncle",
            card_fields: [],
            internal_property: "has nephews",
        });
    });

    describe("text card fields", function () {
        it("Given an item with a string card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "string", value: "Histoire de Toto" }],
                },
            ];

            var filtered_items = in_properties_filter(items, "toto");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a text card field, when I filter it with a matching query, then it will be returned", () => {
            const items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "text", format: "text", value: "Histoire de Toto" }],
                },
            ];

            const filtered_items = in_properties_filter(items, "toto");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a text card field in HTML, when I filter it with a matching query, then it will be returned", () => {
            const items = [
                {
                    id: null,
                    label: null,
                    card_fields: [
                        {
                            type: "text",
                            format: "html",
                            value: "Histoire <strong>de TOTO</strong>",
                        },
                    ],
                },
            ];

            expect(in_properties_filter(items, "toto")).toEqual(items);
        });

        it("Given an item with a text card field in HTML with &nbsp;, when I filter it with an &, then it won't be returned", () => {
            const items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "text", format: "html", value: "William&nbspWallace" }],
                },
            ];

            expect(in_properties_filter(items, "&")).toEqual([]);
        });

        it("Given an item with an int card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "int", value: 123 }],
                },
            ];

            var filtered_items = in_properties_filter(items, "123");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a float card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "float", value: 3.14 }],
                },
            ];

            var filtered_items = in_properties_filter(items, "3.14");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with an artifact id card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "aid", value: 42 }],
                },
            ];

            var filtered_items = in_properties_filter(items, "42");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a per-tracker id card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "atid", value: 86 }],
                },
            ];

            var filtered_items = in_properties_filter(items, "86");

            expect(filtered_items).toEqual(items);
        });

        describe("Given an item with a computed card field,", function () {
            it("when I filter it with a matching query for its auto-computed value, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "computed",
                                manual_value: null,
                                value: 94,
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "94");

                expect(filtered_items).toEqual(items);
            });

            it("when I filter it with a matching query for its manul value, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "computed",
                                manual_value: 61,
                                value: null,
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "61");

                expect(filtered_items).toEqual(items);
            });
        });

        it("Given an item with a priority card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "priority", value: 3 }],
                },
            ];

            var filtered_items = in_properties_filter(items, "3");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a file attachment card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [
                        { type: "file", file_descriptions: [{ name: "Photo de Toto.png" }] },
                    ],
                },
            ];

            var filtered_items = in_properties_filter(items, "toto");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a cross-reference card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "cross", value: [{ ref: "release #46" }] }],
                },
            ];

            var filtered_items = in_properties_filter(items, "46");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a permission card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [{ type: "perm", granted_groups: ["toto"] }],
                },
            ];

            var filtered_items = in_properties_filter(items, "toto");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a submitted by card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [
                        {
                            type: "subby",
                            value: { display_name: "Mr Toto" },
                        },
                    ],
                },
            ];

            var filtered_items = in_properties_filter(items, "toto");

            expect(filtered_items).toEqual(items);
        });

        it("Given an item with a last updated by card field, when I filter it with a matching query, then it will be returned", function () {
            var items = [
                {
                    id: null,
                    label: null,
                    card_fields: [
                        {
                            type: "luby",
                            value: { display_name: "Mr Pototo" },
                        },
                    ],
                },
            ];

            var filtered_items = in_properties_filter(items, "toto");

            expect(filtered_items).toEqual(items);
        });

        describe("Given an item with a selectbox card field", function () {
            it("bound to static values, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "sb",
                                values: [{ label: "Reopen" }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "open");

                expect(filtered_items).toEqual(items);
            });

            it("bound to users, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "sb",
                                values: [{ display_name: "Mr Toto " }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual(items);
            });
        });

        describe("Given an item with a radiobutton card field", function () {
            it("bound to static values, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "rb", values: [{ label: "Reopen" }] }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "open");

                expect(filtered_items).toEqual(items);
            });

            it("bound to users, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "rb",
                                values: [{ display_name: "Mr Toto " }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual(items);
            });
        });

        describe("Given an item with a checkbox card field", function () {
            it("bound to static values, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "cb", values: [{ label: "Reopen" }] }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "open");

                expect(filtered_items).toEqual(items);
            });

            it("bound to users, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "cb",
                                values: [{ display_name: "Mr Toto " }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual(items);
            });
        });

        describe("Given an item with a multiselectbox card field", function () {
            it("bound to static values, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "msb", values: [{ label: "Reopen" }] }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "open");

                expect(filtered_items).toEqual(items);
            });

            it("bound to users, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "msb",
                                values: [{ display_name: "Mr Toto " }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual(items);
            });
        });

        describe("Given an item with an open list card field", function () {
            it("bound to static values when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "tbl", bind_value_objects: [{ label: "Reopen" }] }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "open");

                expect(filtered_items).toEqual(items);
            });

            it("bound to users, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "tbl",
                                bind_value_objects: [{ display_name: "Mr Toto " }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual(items);
            });

            it("when there is no values provided by the api, then it does not encounter an error", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "tbl",
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual([]);
            });
        });

        describe("Given an item with a shared card field", function () {
            it("bound to static values, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "shared", values: [{ label: "Reopen" }] }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "open");

                expect(filtered_items).toEqual(items);
            });

            it("bound to users, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "shared",
                                values: [{ display_name: "Mr Toto " }],
                            },
                        ],
                    },
                ];

                var filtered_items = in_properties_filter(items, "toto");

                expect(filtered_items).toEqual(items);
            });
        });

        describe("date card fields", function () {
            var today;
            beforeEach(function () {
                today = new Date();
            });

            it("Given an item with a date card field, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "date", value: today.toJSON() }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "a few seconds ago");

                expect(filtered_items).toEqual(items);
            });

            it("Given an item with a last updated date card field, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "lud", value: today.toJSON() }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "a few seconds ago");

                expect(filtered_items).toEqual(items);
            });

            it("Given an item with a submitted on card field, when I filter it with a matching query, then it will be returned", function () {
                var items = [
                    {
                        id: null,
                        label: null,
                        card_fields: [{ type: "subon", value: today.toJSON() }],
                    },
                ];

                var filtered_items = in_properties_filter(items, "a few seconds ago");

                expect(filtered_items).toEqual(items);
            });
        });
    });

    describe("Given an artifact with children,", function () {
        it("when I filter with the label of a child, then the artifact will be returned", function () {
            var original_list = [
                {
                    id: 221,
                    label: "cinnamenyl",
                    card_fields: [],
                    children: {
                        loaded: true,
                        data: [
                            {
                                id: 873,
                                label: "gomari",
                                card_fields: [],
                            },
                        ],
                    },
                },
            ];

            var filtered_list = in_properties_filter(original_list, "gomari");

            expect(filtered_list).toEqual(original_list);
        });

        it("and given there were two children, when I filter with the label of one child, then the artifact will be returned with its two children", function () {
            var original_list = [
                {
                    id: 721,
                    label: "brag",
                    card_fields: [],
                    children: {
                        loaded: true,
                        data: [
                            {
                                id: 559,
                                label: "jinshang",
                                card_fields: [],
                            },
                            {
                                id: 190,
                                label: "photistic",
                                card_fields: [],
                            },
                        ],
                    },
                },
            ];

            var filtered_list = in_properties_filter(original_list, "jinshang");

            expect(filtered_list).toEqual(original_list);
        });

        it("and given the children were never loaded, when I filter with the label of a child, then the artifact won't be returned", function () {
            var original_list = [
                {
                    id: 915,
                    label: "Helladotherium",
                    card_fields: [],
                    children: {
                        loaded: false,
                        data: [],
                    },
                },
            ];

            var filtered_list = in_properties_filter(original_list, "gomari");

            expect(filtered_list).toEqual([]);
        });
    });
});
