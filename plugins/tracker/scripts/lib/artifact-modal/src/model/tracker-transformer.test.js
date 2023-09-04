/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { transform, addFieldValuesToTracker } from "./tracker-transformer.js";
import { setCatalog } from "../gettext-catalog";

describe("TuleapArtifactModalTrackerTransformerService", () => {
    beforeEach(() => {
        setCatalog({ getString: (msg) => msg });
    });

    describe("transform() -", () => {
        describe("Given a tracker object with no create permissions fields", () => {
            let tracker, creation_mode;

            beforeEach(() => {
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

            it("and given the modal was opened in creation mode, when I transform the tracker, then the fields with no creation perms will be omitted but the structural ones not from the transformed tracker's fields", () => {
                creation_mode = true;

                const transformed_tracker = transform(tracker, creation_mode);

                expect(transformed_tracker.fields).toStrictEqual([
                    { field_id: 1, type: "int", permissions: ["read", "update", "create"] },
                    { field_id: 2, type: "fieldset", permissions: ["read"] },
                    { field_id: 3, type: "column", permissions: ["read"] },
                ]);
            });
        });

        describe("Given a tracker object", () => {
            let tracker, creation_mode;

            beforeEach(() => {
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

            it("and given the modal was opened in creation mode, when I transform the tracker, then the awkward fields for creation mode (e.g. burndown, subby, subon) will be omitted from the transformed tracker's fields", () => {
                creation_mode = true;

                const transformed_tracker = transform(tracker, creation_mode);

                expect(transformed_tracker.fields).toStrictEqual([
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

            it("and given the modal was opened in edition mode, when I transform the tracker, then the awkward fields for creation mode WILL NOT be omitted from the transformed tracker's fields", () => {
                creation_mode = false;

                const transformed_tracker = transform(tracker, creation_mode);

                expect(transformed_tracker.fields).toStrictEqual([
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

            describe("containing a computed field", () => {
                it("when I transform the tracker, then its value will be set to null by default", () => {
                    const tracker = {
                        fields: [
                            {
                                field_id: 18,
                                permissions: ["read", "update", "create"],
                                type: "computed",
                            },
                        ],
                    };

                    const transformed_tracker = transform(tracker);

                    expect(transformed_tracker.fields[0].value).toBeNull();
                });
            });

            describe(`containing a selectbox field`, () => {
                const FIRST_SELECTBOX_VALUE_ID = 665;

                beforeEach(() => {
                    tracker = {
                        fields: [
                            {
                                field_id: 41,
                                permissions: ["read", "update", "create"],
                                type: "sb",
                                required: false,
                                default_value: [],
                                values: [
                                    { id: FIRST_SELECTBOX_VALUE_ID, is_hidden: false },
                                    { id: 180, is_hidden: false },
                                ],
                            },
                        ],
                    };
                });

                it.each([
                    [
                        "the field has no default value",
                        () => {
                            // no modification
                        },
                    ],
                    [
                        "the field is required",
                        (field) => {
                            field.required = true;
                        },
                    ],
                    [
                        "the field has a default value",
                        (field) => {
                            field.default_value = [{ id: FIRST_SELECTBOX_VALUE_ID }];
                        },
                    ],
                ])(
                    `when %s, then a "None" value will be prepended to its available values`,
                    (_field_description, modifier) => {
                        modifier(tracker.fields[0]);
                        const transformed_tracker = transform(tracker);
                        expect(transformed_tracker.fields[0].values).toContainEqual({
                            id: 100,
                            label: "None",
                        });
                        expect(transformed_tracker.fields[0].values).toHaveLength(3);
                    },
                );

                it(`when the field has a workflow, then the "None" value will not be added`, () => {
                    tracker.fields[0].has_transitions = true;
                    const transformed_tracker = transform(tracker);
                    expect(transformed_tracker.fields[0].values).not.toContainEqual({
                        id: 100,
                        label: "None",
                    });
                    expect(transformed_tracker.fields[0].values).toHaveLength(2);
                });
            });

            describe(`containing a multiselectbox field`, () => {
                const FIRST_MULTI_VALUE_ID = 361;

                beforeEach(() => {
                    tracker = {
                        fields: [
                            {
                                field_id: 22,
                                permissions: ["read", "update", "create"],
                                type: "msb",
                                required: false,
                                default_value: [],
                                values: [
                                    { id: FIRST_MULTI_VALUE_ID, is_hidden: false },
                                    { id: 992, is_hidden: false },
                                ],
                            },
                        ],
                    };
                });

                it.each([
                    [
                        "the field has no default value",
                        () => {
                            // no modification
                        },
                    ],
                    [
                        "the field has a default value",
                        (field) => {
                            field.default_value = [{ id: FIRST_MULTI_VALUE_ID }];
                        },
                    ],
                ])(
                    `when %s, then a "None" value will be prepended to its available values`,
                    (_field_description, modifier) => {
                        modifier(tracker.fields[0]);
                        const transformed_tracker = transform(tracker);
                        expect(transformed_tracker.fields[0].values).toContainEqual({
                            id: 100,
                            label: "None",
                        });
                        expect(transformed_tracker.fields[0].values).toHaveLength(3);
                    },
                );

                it.each([
                    [
                        "the field is required",
                        (field) => {
                            field.required = true;
                        },
                    ],
                    [
                        "the field has a workflow",
                        (field) => {
                            field.has_transitions = true;
                        },
                    ],
                ])(
                    `when %s, then the "None" value will not be added`,
                    (_field_description, modifier) => {
                        modifier(tracker.fields[0]);
                        const transformed_tracker = transform(tracker);
                        expect(transformed_tracker.fields[0].values).not.toContainEqual({
                            id: 100,
                            label: "None",
                        });
                        expect(transformed_tracker.fields[0].values).toHaveLength(2);
                    },
                );
            });

            describe("containing list fields", () => {
                it(", when I transform the tracker, then the field's selectable values will NOT contain any hidden value", () => {
                    const tracker = {
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
                                default_value: [],
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

                    const transformed_tracker = transform(tracker);

                    expect(transformed_tracker.fields[0].values).toStrictEqual([
                        { id: 100, label: "None" },
                        { id: 70, is_hidden: false },
                        { id: 46, is_hidden: false },
                        { id: 35, is_hidden: false },
                    ]);
                    expect(transformed_tracker.fields[1].values).toStrictEqual([
                        { id: 100, label: "None" },
                        { id: 40, is_hidden: false },
                        { id: 41, is_hidden: false },
                    ]);
                    expect(transformed_tracker.fields[2].values).toStrictEqual([
                        { id: 80, is_hidden: false },
                    ]);
                    expect(transformed_tracker.fields[3].values).toStrictEqual([
                        { id: 63, is_hidden: false },
                    ]);
                });

                it("bound to user groups, when I transform the tracker, then the values labels will be internationalized", () => {
                    const tracker = {
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
                                default_value: [],
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

                    const transformed_tracker = transform(tracker);

                    expect(transformed_tracker.fields[0].values[0]).toStrictEqual({
                        id: 100,
                        label: "None",
                    });
                    expect(transformed_tracker.fields[0].values[1]).toStrictEqual(
                        expect.objectContaining({
                            id: "101_3",
                            label: "MSB Group Name",
                        }),
                    );
                    expect(transformed_tracker.fields[0].values[2]).toStrictEqual(
                        expect.objectContaining({
                            id: "101_4",
                            label: "MSB Other Group Name",
                        }),
                    );
                    expect(transformed_tracker.fields[1].values[0]).toStrictEqual({
                        id: 100,
                        label: "None",
                    });
                    expect(transformed_tracker.fields[1].values[1]).toStrictEqual(
                        expect.objectContaining({
                            id: "103_4",
                            label: "SB Group Name",
                        }),
                    );
                    expect(transformed_tracker.fields[2].values[0]).toStrictEqual(
                        expect.objectContaining({
                            id: "108_3",
                            label: "CB Group Name",
                        }),
                    );
                    expect(transformed_tracker.fields[3].values[0]).toStrictEqual(
                        expect.objectContaining({
                            id: "107_4",
                            label: "RB Group Name",
                        }),
                    );
                });
            });

            describe("containing an openlist field", () => {
                it("bound to users, when I transform the tracker, then the field's values will be empty and a loading attribute will be added to the field", () => {
                    const tracker = {
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

                    const transformed_tracker = transform(tracker);

                    expect(transformed_tracker.fields).toStrictEqual([
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

            describe("with field dependencies", () => {
                it("and given that a dependency existed between lists bound to user groups, when I transform the tracker, then the field dependency rules will have their source and target value ids replaced with the corresponding ugroup ids except when the value id is 100 ('none' value)", () => {
                    const tracker = {
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

                    var transformed_tracker = transform(tracker);

                    expect(transformed_tracker.workflow.rules.lists).toStrictEqual([
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

                it("and given that a dependency existed between lists NOT bound to user groups, when I transform the tracker, then the field dependency rules won't be changed", () => {
                    const tracker = {
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

                    const transformed_tracker = transform(tracker);

                    expect(transformed_tracker.workflow.rules.lists).toStrictEqual(
                        tracker.workflow.rules.lists,
                    );
                });
            });
        });
    });

    describe("addFieldValuesToTracker() - Given an artifact's values and given a tracker", () => {
        it("containing a file field, and given said artifact had two attached files including an image, when I add the field's values to the tracker, then the file field will have a file_descriptions attribute containing two objects and the attached image object's display_as_image attribute will be true", () => {
            const artifact_values = {
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
            const tracker = {
                fields: [{ field_id: 719, type: "file" }],
            };

            const transformed_tracker = addFieldValuesToTracker(artifact_values, tracker);

            expect(transformed_tracker).toStrictEqual({
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

        it("containing a perm field, and given said artifact had one granted group, when I add the field's values to the tracker, then the perm field will have an attribute is_used_by_default set to true in its values", () => {
            const artifact_values = {
                18: {
                    field_id: 18,
                    granted_groups: ["101_3"],
                },
            };
            const tracker = {
                fields: [
                    {
                        field_id: 18,
                        type: "perm",
                        values: {},
                    },
                ],
            };

            const transformed_tracker = addFieldValuesToTracker(artifact_values, tracker);

            expect(transformed_tracker).toStrictEqual({
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

        it("containing a computed field, when I add the field's values to the tracker, then the computed field will have a value attribute", () => {
            const artifact_values = {
                146: {
                    field_id: 146,
                    is_autocomputed: false,
                    manual_value: 2,
                    value: null,
                },
            };
            const tracker = {
                fields: [{ field_id: 146, type: "computed" }],
            };

            const transformed_tracker = addFieldValuesToTracker(artifact_values, tracker);

            expect(transformed_tracker).toStrictEqual({
                fields: [
                    {
                        field_id: 146,
                        type: "computed",
                        value: null,
                    },
                ],
            });
        });

        it("when I add the field's values to the tracker, then the awkward fields for creation mode (e.g. burndown, subby, subon) will have a value attribute containing their artifact value so that these values are not submitted", () => {
            const artifact_values = {
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
            const tracker = {
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

            const transformed_tracker = addFieldValuesToTracker(artifact_values, tracker);

            expect(transformed_tracker).toStrictEqual({
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
