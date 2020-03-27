import model_module from "./model.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapArtifactModalTrackerTransformerService", function () {
    var TrackerTransformerService;
    beforeEach(function () {
        angular.mock.module(model_module, function ($provide) {
            $provide.value("translateFilter", function (value) {
                return value;
            });
        });

        angular.mock.inject(function (_TuleapArtifactModalTrackerTransformerService_) {
            TrackerTransformerService = _TuleapArtifactModalTrackerTransformerService_;
        });
    });

    describe("transform() -", function () {
        describe("Given a tracker object with no create permissions fields", function () {
            var tracker, creation_mode;

            beforeEach(function () {
                tracker = {
                    fields: [
                        { field_id: 1, type: "int", permissions: ["read", "update", "create"] },
                        { field_id: 2, type: "fieldset", permissions: ["read"] },
                        { field_id: 3, type: "column", permissions: ["read"] },
                        { field_id: 4, type: "int", permissions: ["read", "update"] },
                        { field_id: 5, type: "priority", permissions: ["read", "update"] },
                        { field_id: 6, type: "perm", permissions: ["read"] },
                    ],
                };
            });

            it("and given the modal was opened in creation mode, when I transform the tracker, then the fields with no creation perms will be omitted but the structural ones not from the transformed tracker's fields", function () {
                creation_mode = true;

                var transformed_tracker = TrackerTransformerService.transform(
                    tracker,
                    creation_mode
                );

                expect(transformed_tracker.fields).toEqual([
                    { field_id: 1, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 2, type: "fieldset", permissions: ["read"] },
                    { field_id: 3, type: "column", permissions: ["read"] },
                ]);
            });
        });

        describe("Given a tracker object", function () {
            var tracker, creation_mode;

            beforeEach(function () {
                tracker = {
                    fields: [
                        { field_id: 1, type: "int", permissions: ["read", "update", "create"] },
                        { field_id: 2, type: "int", permissions: ["read", "update", "create"] },
                        { field_id: 3, type: "fieldset", permissions: ["read"] },
                        { field_id: 4, type: "int", permissions: ["read", "update", "create"] },
                        { field_id: 5, type: "column", permissions: ["read"] },
                        { field_id: 6, type: "int", permissions: ["read", "update", "create"] },
                        { field_id: 7, type: "aid", permissions: ["read"] },
                        { field_id: 8, type: "atid", permissions: ["read"] },
                        { field_id: 9, type: "lud", permissions: ["read"] },
                        { field_id: 10, type: "burndown", permissions: ["read"] },
                        {
                            field_id: 11,
                            type: "priority",
                            permissions: ["read", "update", "create"],
                        },
                        { field_id: 12, type: "subby", permissions: ["read"] },
                        { field_id: 13, type: "subon", permissions: ["read"] },
                        {
                            field_id: 14,
                            type: "computed",
                            permissions: ["read", "update", "create"],
                        },
                        { field_id: 15, type: "cross", permissions: ["read"] },
                        { field_id: 17, type: "perm", permissions: ["read", "update", "create"] },
                        { field_id: 18, type: "luby", permissions: ["read"] },
                    ],
                };
            });

            it("and given the modal was opened in creation mode, when I transform the tracker, then the awkward fields for creation mode (e.g. burndown, subby, subon) will be omitted from the transformed tracker's fields", function () {
                creation_mode = true;

                var transformed_tracker = TrackerTransformerService.transform(
                    tracker,
                    creation_mode
                );

                expect(transformed_tracker.fields).toEqual([
                    { field_id: 1, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 2, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 3, type: "fieldset", permissions: ["read"] },
                    { field_id: 4, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 5, type: "column", permissions: ["read"] },
                    { field_id: 6, type: "int", permissions: ["read", "update", "create"] },
                    {
                        field_id: 14,
                        type: "computed",
                        permissions: ["read", "update", "create"],
                        value: null,
                    },
                    { field_id: 17, type: "perm", permissions: ["read", "update", "create"] },
                ]);
            });

            it("and given the modal was opened in edition mode, when I transform the tracker, then the awkward fields for creation mode WILL NOT be omitted from the transformed tracker's fields", function () {
                creation_mode = false;

                var transformed_tracker = TrackerTransformerService.transform(
                    tracker,
                    creation_mode
                );

                expect(transformed_tracker.fields).toEqual([
                    { field_id: 1, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 2, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 3, type: "fieldset", permissions: ["read"] },
                    { field_id: 4, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 5, type: "column", permissions: ["read"] },
                    { field_id: 6, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 7, type: "aid", permissions: ["read"] },
                    { field_id: 8, type: "atid", permissions: ["read"] },
                    { field_id: 9, type: "lud", permissions: ["read"] },
                    { field_id: 10, type: "burndown", permissions: ["read"] },
                    { field_id: 11, type: "priority", permissions: ["read", "update", "create"] },
                    { field_id: 12, type: "subby", permissions: ["read"] },
                    { field_id: 13, type: "subon", permissions: ["read"] },
                    {
                        field_id: 14,
                        type: "computed",
                        permissions: ["read", "update", "create"],
                        value: null,
                    },
                    { field_id: 15, type: "cross", permissions: ["read"] },
                    { field_id: 17, type: "perm", permissions: ["read", "update", "create"] },
                    { field_id: 18, type: "luby", permissions: ["read"] },
                ]);
            });

            describe("containing a computed field", function () {
                it("when I transform the tracker, then its value will be set to null by default", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 18,
                                permissions: ["read", "update", "create"],
                                type: "computed",
                            },
                        ],
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.fields[0].value).toBe(null);
                });
            });

            describe("containing a selectbox field", function () {
                it("when I transform the tracker, then a 'None' value will be prepended to its selectable values", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 41,
                                permissions: ["read", "update", "create"],
                                type: "sb",
                                values: [
                                    { id: 665, is_hidden: false },
                                    { id: 180, is_hidden: false },
                                ],
                            },
                        ],
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.fields[0].values).toEqual([
                        { id: 100, label: "None" },
                        { id: 665, is_hidden: false },
                        { id: 180, is_hidden: false },
                    ]);
                });
            });

            describe("containing a multiselectbox field", function () {
                it("when I transform the tracker, then a 'None' value will be prepended to its selectable values", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 22,
                                permissions: ["read", "update", "create"],
                                type: "msb",
                                values: [
                                    { id: 361, is_hidden: false },
                                    { id: 992, is_hidden: false },
                                ],
                            },
                        ],
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.fields[0].values).toEqual([
                        { id: 100, label: "None" },
                        { id: 361, is_hidden: false },
                        { id: 992, is_hidden: false },
                    ]);
                });
            });

            describe("containing list fields", function () {
                it(", when I transform the tracker, then the field's selectable values will NOT contain any hidden value", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 3,
                                permissions: ["read", "update", "create"],
                                type: "sb",
                                values: [
                                    { id: 53, is_hidden: true },
                                    { id: 70, is_hidden: false },
                                    { id: 53, is_hidden: true },
                                    { id: 46, is_hidden: false },
                                    { id: 35, is_hidden: false },
                                ],
                            },
                            {
                                field_id: 5,
                                permissions: ["read", "update", "create"],
                                type: "msb",
                                values: [
                                    { id: 40, is_hidden: false },
                                    { id: 41, is_hidden: false },
                                    { id: 20, is_hidden: true },
                                ],
                            },
                            {
                                field_id: 1,
                                permissions: ["read", "update", "create"],
                                type: "cb",
                                values: [
                                    { id: 80, is_hidden: false },
                                    { id: 42, is_hidden: true },
                                ],
                            },
                            {
                                field_id: 8,
                                permissions: ["read", "update", "create"],
                                type: "rb",
                                values: [
                                    { id: 34, is_hidden: true },
                                    { id: 63, is_hidden: false },
                                ],
                            },
                        ],
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.fields[0].values).toEqual([
                        { id: 100, label: "None" },
                        { id: 70, is_hidden: false },
                        { id: 46, is_hidden: false },
                        { id: 35, is_hidden: false },
                    ]);
                    expect(transformed_tracker.fields[1].values).toEqual([
                        { id: 100, label: "None" },
                        { id: 40, is_hidden: false },
                        { id: 41, is_hidden: false },
                    ]);
                    expect(transformed_tracker.fields[2].values).toEqual([
                        { id: 80, is_hidden: false },
                    ]);
                    expect(transformed_tracker.fields[3].values).toEqual([
                        { id: 63, is_hidden: false },
                    ]);
                });

                it("bound to user groups, when I transform the tracker, then the values labels will be internationalized", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 2,
                                permissions: ["read", "update", "create"],
                                type: "msb",
                                values: [
                                    {
                                        id: 607,
                                        label: "group_name",
                                        ugroup_reference: {
                                            id: "101_3",
                                            label: "MSB Group Name",
                                        },
                                    },
                                    {
                                        id: 43,
                                        label: "other_group_name",
                                        ugroup_reference: {
                                            id: "101_4",
                                            label: "MSB Other Group Name",
                                        },
                                    },
                                ],
                            },
                            {
                                field_id: 4,
                                permissions: ["read", "update", "create"],
                                type: "sb",
                                values: [
                                    {
                                        id: 32,
                                        label: "group_name",
                                        is_hidden: false,
                                        ugroup_reference: {
                                            id: "103_4",
                                            label: "SB Group Name",
                                        },
                                    },
                                ],
                            },
                            {
                                field_id: 7,
                                permissions: ["read", "update", "create"],
                                type: "cb",
                                values: [
                                    {
                                        id: 72,
                                        label: "group_name",
                                        is_hidden: false,
                                        ugroup_reference: {
                                            id: "108_3",
                                            label: "CB Group Name",
                                        },
                                    },
                                ],
                            },
                            {
                                field_id: 5,
                                permissions: ["read", "update", "create"],
                                type: "rb",
                                values: [
                                    {
                                        id: 18,
                                        label: "group_name",
                                        is_hidden: false,
                                        ugroup_reference: {
                                            id: "107_4",
                                            label: "RB Group Name",
                                        },
                                    },
                                ],
                            },
                        ],
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.fields[0].values[0]).toEqual({
                        id: 100,
                        label: "None",
                    });
                    expect(transformed_tracker.fields[0].values[1]).toEqual(
                        expect.objectContaining({
                            id: "101_3",
                            label: "MSB Group Name",
                        })
                    );
                    expect(transformed_tracker.fields[0].values[2]).toEqual(
                        expect.objectContaining({
                            id: "101_4",
                            label: "MSB Other Group Name",
                        })
                    );
                    expect(transformed_tracker.fields[1].values[0]).toEqual({
                        id: 100,
                        label: "None",
                    });
                    expect(transformed_tracker.fields[1].values[1]).toEqual(
                        expect.objectContaining({
                            id: "103_4",
                            label: "SB Group Name",
                        })
                    );
                    expect(transformed_tracker.fields[2].values[0]).toEqual(
                        expect.objectContaining({
                            id: "108_3",
                            label: "CB Group Name",
                        })
                    );
                    expect(transformed_tracker.fields[3].values[0]).toEqual(
                        expect.objectContaining({
                            id: "107_4",
                            label: "RB Group Name",
                        })
                    );
                });
            });

            describe("containing an openlist field", function () {
                it("bound to users, when I transform the tracker, then the field's values will be empty and a loading attribute will be added to the field", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 769,
                                bindings: {
                                    type: "users",
                                },
                                label: "starshake",
                                name: "scalably",
                                permissions: ["read", "update", "create"],
                                type: "tbl",
                                values: {
                                    resource: {
                                        type: "users",
                                        uri: "/users/?query=",
                                    },
                                },
                            },
                        ],
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.fields).toEqual([
                        {
                            field_id: 769,
                            bindings: {
                                type: "users",
                            },
                            label: "starshake",
                            name: "scalably",
                            permissions: ["read", "update", "create"],
                            type: "tbl",
                            values: [],
                            loading: false,
                        },
                    ]);
                });
            });

            describe("with field dependencies", function () {
                it("and given that a dependency existed between lists bound to user groups, when I transform the tracker, then the field dependency rules will have their source and target value ids replaced with the corresponding ugroup ids except when the value id is 100 ('none' value)", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 86,
                                bindings: {
                                    type: "ugroups",
                                },
                                type: "sb",
                                values: [
                                    {
                                        id: 764,
                                        ugroup_reference: {
                                            id: "121_3",
                                        },
                                    },
                                    {
                                        id: 366,
                                        ugroup_reference: {
                                            id: "190",
                                        },
                                    },
                                ],
                            },
                            {
                                field_id: 18,
                                bindings: {
                                    type: "ugroups",
                                },
                                type: "msb",
                                values: [
                                    {
                                        id: 145,
                                        ugroup_reference: {
                                            id: "183_4",
                                        },
                                    },
                                    {
                                        id: 923,
                                        ugroup_reference: {
                                            id: "321",
                                        },
                                    },
                                ],
                            },
                        ],
                        workflow: {
                            rules: {
                                lists: [
                                    {
                                        source_field_id: 86,
                                        source_value_id: 764,
                                        target_field_id: 18,
                                        target_value_id: 145,
                                    },
                                    {
                                        source_field_id: 86,
                                        source_value_id: 764,
                                        target_field_id: 18,
                                        target_value_id: 923,
                                    },
                                    {
                                        source_field_id: 86,
                                        source_value_id: 100,
                                        target_field_id: 18,
                                        target_value_id: 145,
                                    },
                                    {
                                        source_field_id: 86,
                                        source_value_id: 366,
                                        target_field_id: 18,
                                        target_value_id: 100,
                                    },
                                ],
                            },
                        },
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.workflow.rules.lists).toEqual([
                        {
                            source_field_id: 86,
                            source_value_id: "121_3",
                            target_field_id: 18,
                            target_value_id: "183_4",
                        },
                        {
                            source_field_id: 86,
                            source_value_id: "121_3",
                            target_field_id: 18,
                            target_value_id: "321",
                        },
                        {
                            source_field_id: 86,
                            source_value_id: 100,
                            target_field_id: 18,
                            target_value_id: "183_4",
                        },
                        {
                            source_field_id: 86,
                            source_value_id: "190",
                            target_field_id: 18,
                            target_value_id: 100,
                        },
                    ]);
                });

                it("and given that a dependency existed between lists NOT bound to user groups, when I transform the tracker, then the field dependency rules won't be changed", function () {
                    var tracker = {
                        fields: [
                            {
                                field_id: 67,
                                bindings: {
                                    type: "users",
                                },
                                type: "sb",
                                values: [
                                    {
                                        id: 599,
                                        user_reference: {
                                            id: "243",
                                        },
                                    },
                                    {
                                        id: 435,
                                        user_reference: {
                                            id: "186",
                                        },
                                    },
                                ],
                            },
                            {
                                field_id: 94,
                                bindings: {
                                    type: "static",
                                },
                                type: "msb",
                                values: [
                                    {
                                        id: 380,
                                    },
                                    {
                                        id: 938,
                                    },
                                ],
                            },
                        ],
                        workflow: {
                            rules: {
                                lists: [
                                    {
                                        source_field_id: 67,
                                        source_value_id: 100,
                                        target_field_id: 94,
                                        target_value_id: 938,
                                    },
                                    {
                                        source_field_id: 67,
                                        source_value_id: 599,
                                        target_field_id: 94,
                                        target_value_id: 380,
                                    },
                                    {
                                        source_field_id: 67,
                                        source_value_id: 435,
                                        target_field_id: 94,
                                        target_value_id: 100,
                                    },
                                    {
                                        source_field_id: 67,
                                        source_value_id: 435,
                                        target_field_id: 94,
                                        target_value_id: 938,
                                    },
                                ],
                            },
                        },
                    };

                    var transformed_tracker = TrackerTransformerService.transform(tracker);

                    expect(transformed_tracker.workflow.rules.lists).toEqual(
                        tracker.workflow.rules.lists
                    );
                });
            });
        });
    });

    describe("addFieldValuesToTracker() - Given an artifact's values and given a tracker", function () {
        it("containing a file field, and given said artifact had two attached files including an image, when I add the field's values to the tracker, then the file field will have a file_descriptions attribute containing two objects and the attached image object's display_as_image attribute will be true", function () {
            var artifact_values = {
                719: {
                    field_id: 719,
                    file_descriptions: [
                        {
                            type: "image/png",
                            somekey: "somevalue",
                        },
                        {
                            type: "text/xml",
                            someotherkey: "differentvalue",
                        },
                    ],
                },
            };
            var tracker = {
                fields: [{ field_id: 719, type: "file" }],
            };

            var transformed_tracker = TrackerTransformerService.addFieldValuesToTracker(
                artifact_values,
                tracker
            );

            expect(transformed_tracker).toEqual({
                fields: [
                    {
                        field_id: 719,
                        type: "file",
                        file_descriptions: [
                            {
                                type: "image/png",
                                display_as_image: true,
                                somekey: "somevalue",
                            },
                            {
                                type: "text/xml",
                                display_as_image: false,
                                someotherkey: "differentvalue",
                            },
                        ],
                    },
                ],
            });
        });

        it("containing a perm field, and given said artifact had one granted group, when I add the field's values to the tracker, then the perm field will have an attribute is_used_by_default set to true in its values", function () {
            var artifact_values = {
                18: {
                    field_id: 18,
                    granted_groups: ["101_3"],
                },
            };
            var tracker = {
                fields: [
                    {
                        field_id: 18,
                        type: "perm",
                        values: {},
                    },
                ],
            };

            var transformed_tracker = TrackerTransformerService.addFieldValuesToTracker(
                artifact_values,
                tracker
            );

            expect(transformed_tracker).toEqual({
                fields: [
                    {
                        field_id: 18,
                        type: "perm",
                        values: {
                            is_used_by_default: true,
                        },
                    },
                ],
            });
        });

        it("containing a computed field, when I add the field's values to the tracker, then the computed field will have a value attribute", function () {
            var artifact_values = {
                146: {
                    field_id: 146,
                    is_autocomputed: false,
                    manual_value: 2,
                    value: null,
                },
            };
            var tracker = {
                fields: [{ field_id: 146, type: "computed" }],
            };

            var transformed_tracker = TrackerTransformerService.addFieldValuesToTracker(
                artifact_values,
                tracker
            );

            expect(transformed_tracker).toEqual({
                fields: [
                    {
                        field_id: 146,
                        type: "computed",
                        value: null,
                    },
                ],
            });
        });

        it("when I add the field's values to the tracker, then the awkward fields for creation mode (e.g. burndown, subby, subon) will have a value attribute containing their artifact value so that these values are not submitted", function () {
            var artifact_values = {
                1: {
                    field_id: 1,
                    value: "beliefful",
                },
                2: {
                    field_id: 2,
                    bind_value_ids: [48],
                },
                3: {
                    field_id: 3,
                    value: 21,
                },
                4: {
                    field_id: 4,
                    value: 99,
                },
                5: {
                    field_id: 5,
                    value: "2014-06-13T06:29:45+10:00",
                },
                6: {
                    field_id: 6,
                    value: {
                        capacity: 24,
                        duration: 85,
                        points: [],
                    },
                },
                7: {
                    field_id: 7,
                    value: 51,
                },
                8: {
                    field_id: 8,
                    value: {
                        email: "creditrix@bothy.com",
                    },
                },
                9: {
                    field_id: 9,
                    value: "2012-03-06T15:13:55+06:00",
                },
                11: {
                    field_id: 11,
                    value: {
                        ref: "rel #10",
                        url: "http://biphasic.com/psychotherapeutics/mountant?a=auxocyte",
                    },
                },
                12: {
                    field_id: 12,
                    value: {
                        email: "archhost@rummy.co.uk",
                    },
                },
            };
            var tracker = {
                fields: [
                    { field_id: 1, type: "string" },
                    { field_id: 2, type: "sb" },
                    { field_id: 3, type: "aid" },
                    { field_id: 4, type: "atid" },
                    { field_id: 5, type: "lud" },
                    { field_id: 6, type: "burndown" },
                    { field_id: 7, type: "priority" },
                    { field_id: 8, type: "subby" },
                    { field_id: 9, type: "subon" },
                    { field_id: 11, type: "cross" },
                    { field_id: 12, type: "luby" },
                ],
            };

            var transformed_tracker = TrackerTransformerService.addFieldValuesToTracker(
                artifact_values,
                tracker
            );

            expect(transformed_tracker).toEqual({
                fields: [
                    { field_id: 1, type: "string" },
                    { field_id: 2, type: "sb" },
                    {
                        field_id: 3,
                        type: "aid",
                        value: 21,
                    },
                    {
                        field_id: 4,
                        type: "atid",
                        value: 99,
                    },
                    {
                        field_id: 5,
                        type: "lud",
                        value: "2014-06-13T06:29:45+10:00",
                    },
                    {
                        field_id: 6,
                        type: "burndown",
                        value: {
                            capacity: 24,
                            duration: 85,
                            points: [],
                        },
                    },
                    {
                        field_id: 7,
                        type: "priority",
                        value: 51,
                    },
                    {
                        field_id: 8,
                        type: "subby",
                        value: {
                            email: "creditrix@bothy.com",
                        },
                    },
                    {
                        field_id: 9,
                        type: "subon",
                        value: "2012-03-06T15:13:55+06:00",
                    },
                    {
                        field_id: 11,
                        type: "cross",
                        value: {
                            ref: "rel #10",
                            url: "http://biphasic.com/psychotherapeutics/mountant?a=auxocyte",
                        },
                    },
                    {
                        field_id: 12,
                        type: "luby",
                        value: {
                            email: "archhost@rummy.co.uk",
                        },
                    },
                ],
            });
        });
    });
});
