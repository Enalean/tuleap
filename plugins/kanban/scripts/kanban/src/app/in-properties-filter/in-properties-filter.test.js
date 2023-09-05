import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";

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
            },
            {
                label: "Loulou",
                id: "nephew",
                card_fields: [],
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
        ];

    beforeEach(() => {
        angular.mock.module(kanban_module);

        var $filter, moment;
        angular.mock.inject(function (_$filter_, _moment_) {
            $filter = _$filter_;
            moment = _moment_;
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
        expect(in_properties_filter(list, "Donald")).not.toContain({
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
        });
        expect(in_properties_filter(list, "nephew")).toContainEqual({
            label: "Loulou",
            id: "nephew",
            card_fields: [],
        });
        expect(in_properties_filter(list, "nephew")).not.toContain({
            label: "Donald & Daisy",
            id: "significant others",
            card_fields: [],
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
        expect(in_properties_filter(list, "donald daisy")).not.toContain({
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
        expect(in_properties_filter(list, "daisy donald")).not.toContain({
            label: "Donald",
            id: "uncle",
            card_fields: [],
            internal_property: "has nephews",
        });
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
                    { type: "text", format: "html", value: "Histoire <strong>de TOTO</strong>" },
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

    it("returns items that have matching card_fields", function () {
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "string",
                                value: "Histoire de Toto",
                            },
                        ],
                    },
                ],
                "toto",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "int",
                                value: 123,
                            },
                        ],
                    },
                ],
                "123",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "float",
                                value: 3.14,
                            },
                        ],
                    },
                ],
                "3.14",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "aid",
                                value: 42,
                            },
                        ],
                    },
                ],
                "42",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "atid",
                                value: 42,
                            },
                        ],
                    },
                ],
                "42",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "priority",
                                value: 42,
                            },
                        ],
                    },
                ],
                "42",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "file",
                                file_descriptions: [
                                    {
                                        name: "Photo de Toto.png",
                                    },
                                ],
                            },
                        ],
                    },
                ],
                "toto",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "cross",
                                value: [
                                    {
                                        ref: "release #42",
                                    },
                                ],
                            },
                        ],
                    },
                ],
                "42",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "perm",
                                granted_groups: ["toto"],
                            },
                        ],
                    },
                ],
                "toto",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "subby",
                                value: {
                                    display_name: "Mr Toto",
                                },
                            },
                        ],
                    },
                ],
                "toto",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "luby",
                                value: {
                                    display_name: "Mr Pototo",
                                },
                            },
                        ],
                    },
                ],
                "toto",
            ),
        ).toHaveLength(1);
        ["sb", "rb", "cb", "msb", "shared"].forEach(function (list_type) {
            expect(
                in_properties_filter(
                    [
                        {
                            id: null,
                            label: null,
                            card_fields: [
                                {
                                    type: list_type,
                                    values: [
                                        {
                                            label: "Reopen",
                                        },
                                    ],
                                },
                            ],
                        },
                    ],
                    "open",
                ),
            ).toHaveLength(1);
            expect(
                in_properties_filter(
                    [
                        {
                            id: null,
                            label: null,
                            card_fields: [
                                {
                                    type: list_type,
                                    values: [
                                        {
                                            display_name: "Mr Toto",
                                        },
                                    ],
                                },
                            ],
                        },
                    ],
                    "toto",
                ),
            ).toHaveLength(1);
            expect(
                in_properties_filter(
                    [
                        {
                            id: null,
                            label: null,
                            card_fields: [
                                {
                                    type: list_type,
                                },
                            ],
                        },
                    ],
                    "toto",
                ),
            ).toHaveLength(0);
        });
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "tbl",
                                bind_value_objects: [{ label: "Mr Toto" }],
                            },
                        ],
                    },
                ],
                "toto",
            ),
        ).toHaveLength(1);
        ["date", "lud", "subon"].forEach(function (date_type) {
            var today = new Date();

            expect(
                in_properties_filter(
                    [
                        {
                            id: null,
                            label: null,
                            card_fields: [
                                {
                                    type: date_type,
                                    value: today.toJSON(),
                                },
                            ],
                        },
                    ],
                    "today",
                ),
            ).toHaveLength(1);
        });
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "computed",
                                manual_value: null,
                                value: 42,
                            },
                        ],
                    },
                ],
                "42",
            ),
        ).toHaveLength(1);
        expect(
            in_properties_filter(
                [
                    {
                        id: null,
                        label: null,
                        card_fields: [
                            {
                                type: "computed",
                                manual_value: 42,
                                value: null,
                            },
                        ],
                    },
                ],
                "42",
            ),
        ).toHaveLength(1);
    });

    it("Given no terms to filter with, when I filter a list of items, then a copy of this list with the same items will be returned", function () {
        var list = [{ id: 28 }, { id: 94 }, { id: 69 }];

        var filtered_list = in_properties_filter(list, "");

        expect(filtered_list).toEqual(list);
        expect(filtered_list).not.toBe(list);
    });
});
