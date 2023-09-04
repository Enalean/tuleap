/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import {
    getPostArtifactBody,
    getPutArtifactBody,
    getPutArtifactBodyToAddChild,
} from "./update-artifact";
import type { NewCardPayload, UpdateCardPayload } from "../store/swimlane/card/type";
import type { ListValue, Mapping, Tracker } from "../type";
import type { ListField, TextField, Values } from "../store/swimlane/card/api-artifact-type";

describe("update-artifact", () => {
    describe("getPutArtifactBody", () => {
        it("Raises an error if the title_field is not present", () => {
            expect(() =>
                getPutArtifactBody({ tracker: { title_field: null } } as UpdateCardPayload),
            ).toThrow();
        });

        it("Sets the value of a string field", () => {
            const body = getPutArtifactBody({
                tracker: { title_field: { id: 123, is_string_field: true } },
                label: "Lorem ipsum",
            } as UpdateCardPayload);

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 123,
                        value: "Lorem ipsum",
                    },
                ],
            });
        });

        it("Remove linebreak characters for string field", () => {
            const body = getPutArtifactBody({
                tracker: { title_field: { id: 123, is_string_field: true } },
                label: "Lorem\n\nipsum\n",
            } as UpdateCardPayload);

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 123,
                        value: "Lorem ipsum ",
                    },
                ],
            });
        });

        it("Sets the value of a text field", () => {
            const body = getPutArtifactBody({
                tracker: { title_field: { id: 123, is_string_field: false } },
                label: "Lorem ipsum",
            } as UpdateCardPayload);

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 123,
                        value: {
                            content: "Lorem ipsum",
                            format: "text",
                        },
                    },
                ],
            });
        });

        it("Keeps linebreak characters for text field", () => {
            const body = getPutArtifactBody({
                tracker: { title_field: { id: 123, is_string_field: false } },
                label: "Lorem\n\nipsum\n",
            } as UpdateCardPayload);

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 123,
                        value: {
                            content: "Lorem\n\nipsum\n",
                            format: "text",
                        },
                    },
                ],
            });
        });

        it("Sets the value of assigned to field", () => {
            const body = getPutArtifactBody({
                tracker: {
                    title_field: { id: 123, is_string_field: false },
                    assigned_to_field: { id: 124 },
                } as Tracker,
                assignees: [{ id: 1001 }, { id: 1002 }],
                label: "Lorem ipsum",
            } as UpdateCardPayload);

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 123,
                        value: {
                            content: "Lorem ipsum",
                            format: "text",
                        },
                    } as TextField,
                    {
                        field_id: 124,
                        bind_value_ids: [1001, 1002],
                    } as ListField,
                ],
            });
        });
    });

    describe("getPostArtifactBody", () => {
        it("Raises an error if the parent tracker is not present", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                    } as NewCardPayload,
                    [] as Tracker[],
                ),
            ).toThrow();
        });

        it("Raises an error if add_in_place is not present", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                    } as NewCardPayload,
                    [{ id: 42, add_in_place: null } as Tracker],
                ),
            ).toThrow();
        });

        it("Raises an error if the child tracker is not present", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                    } as NewCardPayload,
                    [
                        {
                            id: 42,
                            add_in_place: {
                                child_tracker_id: 69,
                                parent_artifact_link_field_id: 103,
                            },
                        } as Tracker,
                    ],
                ),
            ).toThrow();
        });

        it("Raises an error if the title_field is not present", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                    } as NewCardPayload,
                    [
                        {
                            id: 42,
                            add_in_place: {
                                child_tracker_id: 69,
                                parent_artifact_link_field_id: 103,
                            },
                        } as Tracker,
                        { id: 69, title_field: null } as Tracker,
                    ],
                ),
            ).toThrow();
        });

        it("Raises an error if mapping cannot be found", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                        column: {
                            mappings: [] as Mapping[],
                        },
                    } as NewCardPayload,
                    [
                        {
                            id: 42,
                            add_in_place: {
                                child_tracker_id: 69,
                                parent_artifact_link_field_id: 103,
                            },
                        } as Tracker,
                        { id: 69, title_field: { id: 123, is_string_field: true } } as Tracker,
                    ],
                ),
            ).toThrow();
        });

        it("Raises an error if artifact link field is not present", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                        column: {
                            mappings: [] as Mapping[],
                        },
                    } as NewCardPayload,
                    [
                        {
                            id: 42,
                            add_in_place: {
                                child_tracker_id: 69,
                                parent_artifact_link_field_id: 103,
                            },
                        } as Tracker,
                        {
                            id: 69,
                            title_field: { id: 123, is_string_field: true },
                            artifact_link_field: null,
                        } as Tracker,
                    ],
                ),
            ).toThrow();
        });

        it("Raises an error if there is no mapping field", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                        column: {
                            mappings: [{ tracker_id: 69, field_id: null }],
                        },
                    } as NewCardPayload,
                    [
                        {
                            id: 42,
                            add_in_place: {
                                child_tracker_id: 69,
                                parent_artifact_link_field_id: 103,
                            },
                        } as Tracker,
                        {
                            id: 69,
                            title_field: { id: 123, is_string_field: true },
                            artifact_link_field: { id: 111 },
                        } as Tracker,
                    ],
                ),
            ).toThrow();
        });

        it("Raises an error if there is no mapping field value", () => {
            expect(() =>
                getPostArtifactBody(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                        column: {
                            mappings: [
                                { tracker_id: 69, field_id: 666, accepts: [] as ListValue[] },
                            ],
                        },
                    } as NewCardPayload,
                    [
                        {
                            id: 42,
                            add_in_place: {
                                child_tracker_id: 69,
                                parent_artifact_link_field_id: 103,
                            },
                        } as Tracker,
                        {
                            id: 69,
                            title_field: { id: 123, is_string_field: true },
                            artifact_link_field: { id: 111 },
                        } as Tracker,
                    ],
                ),
            ).toThrow();
        });

        it("Returns the body with string label and mapped field value", () => {
            const body = getPostArtifactBody(
                {
                    swimlane: { card: { id: 74, tracker_id: 42 } },
                    column: {
                        mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                    },
                    label: "Lorem ipsum",
                } as NewCardPayload,
                [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                    {
                        id: 69,
                        title_field: { id: 123, is_string_field: true },
                        artifact_link_field: { id: 111 },
                    } as Tracker,
                ],
            );

            expect(body).toStrictEqual({
                tracker: {
                    id: 69,
                },
                values: [
                    {
                        field_id: 123,
                        value: "Lorem ipsum",
                    },
                    {
                        field_id: 666,
                        bind_value_ids: [101],
                    },
                ],
            });
        });

        it("Remove linebreak characters for string field", () => {
            const body = getPostArtifactBody(
                {
                    swimlane: { card: { id: 74, tracker_id: 42 } },
                    column: {
                        mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                    },
                    label: "Lorem\n\nipsum\n",
                } as NewCardPayload,
                [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                    {
                        id: 69,
                        title_field: { id: 123, is_string_field: true },
                        artifact_link_field: { id: 111 },
                    } as Tracker,
                ],
            );

            expect(body).toStrictEqual({
                tracker: {
                    id: 69,
                },
                values: [
                    {
                        field_id: 123,
                        value: "Lorem ipsum ",
                    },
                    {
                        field_id: 666,
                        bind_value_ids: [101],
                    },
                ],
            });
        });

        it("Returns the body with text label and mapped field value", () => {
            const body = getPostArtifactBody(
                {
                    swimlane: { card: { id: 74, tracker_id: 42 } },
                    column: {
                        mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                    },
                    label: "Lorem ipsum",
                } as NewCardPayload,
                [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                    {
                        id: 69,
                        title_field: { id: 123, is_string_field: false },
                        artifact_link_field: { id: 111 },
                    } as Tracker,
                ],
            );

            expect(body).toStrictEqual({
                tracker: {
                    id: 69,
                },
                values: [
                    {
                        field_id: 123,
                        value: {
                            content: "Lorem ipsum",
                            format: "text",
                        },
                    },
                    {
                        field_id: 666,
                        bind_value_ids: [101],
                    },
                ],
            });
        });

        it("Remove linebreak characters for text field", () => {
            const body = getPostArtifactBody(
                {
                    swimlane: { card: { id: 74, tracker_id: 42 } },
                    column: {
                        mappings: [{ tracker_id: 69, field_id: 666, accepts: [{ id: 101 }] }],
                    },
                    label: "Lorem\n\nipsum\n",
                } as NewCardPayload,
                [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                    {
                        id: 69,
                        title_field: { id: 123, is_string_field: false },
                        artifact_link_field: { id: 111 },
                    } as Tracker,
                ],
            );

            expect(body).toStrictEqual({
                tracker: {
                    id: 69,
                },
                values: [
                    {
                        field_id: 123,
                        value: {
                            content: "Lorem\n\nipsum\n",
                            format: "text",
                        },
                    },
                    {
                        field_id: 666,
                        bind_value_ids: [101],
                    },
                ],
            });
        });
    });

    describe("getPutArtifactBodyToAddChild", () => {
        it("Raises an error if the parent tracker is not present", () => {
            expect(() =>
                getPutArtifactBodyToAddChild(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                    } as NewCardPayload,
                    [] as Tracker[],
                    1001,
                    [] as Values,
                ),
            ).toThrow();
        });

        it("Raises an error if add_in_place is not present", () => {
            expect(() =>
                getPutArtifactBodyToAddChild(
                    {
                        swimlane: { card: { tracker_id: 42 } },
                    } as NewCardPayload,
                    [{ id: 42, add_in_place: null } as Tracker],
                    1001,
                    [] as Values,
                ),
            ).toThrow();
        });

        it("Sets the value for artifact link field", () => {
            const body = getPutArtifactBodyToAddChild(
                {
                    swimlane: { card: { tracker_id: 42 } },
                } as NewCardPayload,
                [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                ],
                1001,
                [] as Values,
            );

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 103,
                        links: [
                            {
                                id: 1001,
                                type: "_is_child",
                            },
                        ],
                    },
                ],
            });
        });

        it("Keeps the existing links so that we don't loose them while updating the artifact", () => {
            const body = getPutArtifactBodyToAddChild(
                {
                    swimlane: { card: { tracker_id: 42 } },
                } as NewCardPayload,
                [
                    {
                        id: 42,
                        add_in_place: { child_tracker_id: 69, parent_artifact_link_field_id: 103 },
                    } as Tracker,
                ],
                1001,
                [{ field_id: 103, links: [{ id: 999, type: "custom" }] }] as Values,
            );

            expect(body).toStrictEqual({
                values: [
                    {
                        field_id: 103,
                        links: [
                            {
                                id: 999,
                                type: "custom",
                            },
                            {
                                id: 1001,
                                type: "_is_child",
                            },
                        ],
                    },
                ],
            });
        });
    });
});
