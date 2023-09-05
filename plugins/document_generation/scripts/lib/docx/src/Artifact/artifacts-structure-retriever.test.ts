/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import * as rest_querier from "./artifacts-retriever";
import type {
    ArtifactReportResponseUserRepresentation,
    ArtifactResponse,
    TestExecutionResponse,
    TrackerStructure,
} from "../type";
import { retrieveArtifactsStructure } from "./artifacts-structure-retriever";
import type { TrackerResponseWithColor } from "@tuleap/plugin-tracker-rest-api-types";

describe("retrieveArtifactsStructure", () => {
    it("retrieves full artifacts structure", async () => {
        const artifacts_report_response: ArtifactResponse[] = [
            {
                id: 74,
                title: null,
                xref: "bug #74",
                tracker: { id: 102 } as TrackerResponseWithColor,
                html_url: "/plugins/tracker/?aid=74",
                status: "irrelevant",
                is_open: true,
                values: [
                    {
                        field_id: 1,
                        type: "date",
                        label: "My Date",
                        value: "2021-07-30T15:56:09+02:00",
                    },
                    {
                        field_id: 2,
                        type: "date",
                        label: "My Other Date",
                        value: "2021-07-01T00:00:00+02:00",
                    },
                    {
                        field_id: 3,
                        type: "string",
                        label: "Some String",
                        value: null,
                    },
                    {
                        field_id: 5,
                        type: "msb",
                        label: "User List",
                        values: [
                            {
                                email: "email_address",
                                status: "A",
                                id: 101,
                                uri: "users/101",
                                user_url: "/users/user01",
                                real_name: "User 01",
                                display_name: "User 01 (user01)",
                                username: "user01",
                                ldap_id: "101",
                                avatar_url: "https://example.com/users/user01/avatar-abcdef.png",
                                is_anonymous: false,
                                has_avatar: true,
                            },
                            {
                                email: "email_address_02",
                                status: "A",
                                id: 102,
                                uri: "users/102",
                                user_url: "/users/user02",
                                real_name: "User 02",
                                display_name: "User 02 (user02)",
                                username: "user02",
                                ldap_id: "",
                                avatar_url: "https://example.com/users/user02/avatar-qwerty.png",
                                is_anonymous: false,
                                has_avatar: true,
                            },
                            {
                                id: null,
                            } as ArtifactReportResponseUserRepresentation,
                        ],
                    },
                    {
                        field_id: 6,
                        type: "sb",
                        label: "Static List",
                        values: [
                            {
                                id: "4",
                                label: "Value01",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                    },
                    {
                        field_id: 7,
                        type: "rb",
                        label: "Ugroups List",
                        values: [
                            {
                                id: "101_3",
                                uri: "user_groups/101_3",
                                label: "Membres du projet",
                                users_uri: "user_groups/101_3/users",
                                short_name: "project_members",
                                key: "ugroup_project_members_name_key",
                            },
                        ],
                    },
                    {
                        field_id: 8,
                        type: "cb",
                        label: "Checkbox List",
                        values: [
                            {
                                id: "15",
                                label: "MulitValue01",
                                color: null,
                                tlp_color: null,
                            },
                            {
                                id: "16",
                                label: "MulitValue02",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                    },
                    {
                        field_id: 9,
                        type: "tbl",
                        label: "Open List",
                        bind_value_objects: [
                            {
                                id: "1",
                                label: "azerty",
                            },
                            {
                                id: "12548",
                                label: "OpenValue01",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                    },
                    {
                        field_id: 10,
                        type: "perm",
                        label: "Permissions",
                        granted_groups: ["membres_projet", "newgroup"],
                        granted_groups_ids: ["101_3", "105"],
                    },
                    {
                        field_id: 12,
                        type: "art_link",
                        label: "Links",
                        links: [
                            {
                                type: "_is_child",
                                id: 359,
                            },
                        ],
                        reverse_links: [
                            {
                                type: null,
                                id: 3,
                            },
                        ],
                    },
                ],
            },
        ];

        vi.spyOn(rest_querier, "getArtifacts").mockResolvedValue(
            new Map([
                [
                    359,
                    {
                        id: 359,
                        xref: "bug #359",
                        tracker: { id: 102 } as TrackerResponseWithColor,
                        html_url: "/plugins/tracker/?aid=359",
                        title: "Linked artifact",
                        status: "irrelevant",
                        is_open: true,
                        values: [],
                    },
                ],
                [
                    3,
                    {
                        id: 3,
                        xref: "bug #3",
                        tracker: { id: 102 } as TrackerResponseWithColor,
                        html_url: "/plugins/tracker/?aid=3",
                        title: "Reverse linked artifact",
                        status: "irrelevant",
                        is_open: true,
                        values: [],
                    },
                ],
            ]),
        );

        const tracker_structure: TrackerStructure = {
            fields: new Map([
                [2, { field_id: 2, type: "date", is_time_displayed: false }],
                [4, { field_id: 4, type: "fieldset", label: "Fieldset label" }],
                [5, { field_id: 5, type: "msb" }],
                [6, { field_id: 6, type: "sb" }],
                [7, { field_id: 7, type: "rb" }],
                [8, { field_id: 8, type: "cb" }],
                [9, { field_id: 9, type: "tbl" }],
                [
                    10,
                    {
                        field_id: 10,
                        type: "perm",
                        values: {
                            is_used_by_default: false,
                            ugroup_representations: [
                                {
                                    id: "101_3",
                                    uri: "user_groups/101_3",
                                    label: "Membres du projet",
                                    users_uri: "user_groups/101_3/users",
                                    short_name: "project_members",
                                    key: "ugroup_project_members_name_key",
                                },
                                {
                                    id: "105",
                                    uri: "user_groups/105",
                                    label: "newgroup",
                                    users_uri: "user_groups/105/users",
                                    short_name: "newgroup",
                                    key: "newgroup",
                                },
                            ],
                        },
                    },
                ],
                [11, { field_id: 11, type: "ttmstepexec", label: "Test Execution" }],
                [12, { field_id: 12, type: "art_link" }],
            ]),
            disposition: [
                { id: 3, content: null },
                { id: 5, content: null },
                { id: 6, content: null },
                { id: 7, content: null },
                { id: 8, content: null },
                { id: 9, content: null },
                { id: 10, content: null },
                { id: 11, content: null },
                {
                    id: 4,
                    content: [
                        { id: 2, content: null },
                        { id: 1, content: null },
                    ],
                },
                { id: 12, content: null },
            ],
        };

        const testmanagement_execution_response: TestExecutionResponse = {
            definition: {
                artifact: {
                    id: 359,
                } as ArtifactResponse,
                id: 359,
                summary: "Summary",
                description: "",
                description_format: "text",
                steps: [
                    {
                        id: 13,
                        description: "01",
                        description_format: "text",
                        expected_results: "01",
                        expected_results_format: "text",
                        rank: 1,
                    },
                    {
                        id: 14,
                        description: "This is text",
                        description_format: "text",
                        expected_results: "text\nwith\nnewlines",
                        expected_results_format: "text",
                        rank: 2,
                    },
                    {
                        id: 15,
                        description: "<p>This is HTML</p>",
                        description_format: "html",
                        expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                        expected_results_format: "html",
                        rank: 3,
                    },
                ],
                all_requirements: [
                    {
                        id: 888,
                        title: null,
                        xref: "story #888",
                        tracker: {
                            id: 111,
                        },
                    },
                ],
            },
            previous_result: null,
            steps_results: {
                "13": {
                    step_id: 13,
                    status: "passed",
                },
                "15": {
                    step_id: 15,
                    status: "blocked",
                },
            },
            status: "blocked",
            attachments: [],
            linked_bugs: [],
        };
        const get_test_exec = (): Promise<TestExecutionResponse> =>
            Promise.resolve(testmanagement_execution_response);

        const artifacts = await retrieveArtifactsStructure(
            new Map([[102, tracker_structure]]),
            artifacts_report_response,
            get_test_exec,
        );

        expect(artifacts).toStrictEqual([
            {
                id: 74,
                xref: "bug #74",
                tracker: { id: 102 },
                title: null,
                html_url: "/plugins/tracker/?aid=74",
                status: "irrelevant",
                is_open: true,
                values: [
                    {
                        field_id: 3,
                        label: "Some String",
                        type: "string",
                        value: null,
                    },
                    {
                        field_id: 5,
                        type: "msb",
                        label: "User List",
                        values: [
                            {
                                email: "email_address",
                                status: "A",
                                id: 101,
                                uri: "users/101",
                                user_url: "/users/user01",
                                real_name: "User 01",
                                display_name: "User 01 (user01)",
                                username: "user01",
                                ldap_id: "101",
                                avatar_url: "https://example.com/users/user01/avatar-abcdef.png",
                                is_anonymous: false,
                                has_avatar: true,
                            },
                            {
                                email: "email_address_02",
                                status: "A",
                                id: 102,
                                uri: "users/102",
                                user_url: "/users/user02",
                                real_name: "User 02",
                                display_name: "User 02 (user02)",
                                username: "user02",
                                ldap_id: "",
                                avatar_url: "https://example.com/users/user02/avatar-qwerty.png",
                                is_anonymous: false,
                                has_avatar: true,
                            },
                            {
                                id: null,
                            } as ArtifactReportResponseUserRepresentation,
                        ],
                        formatted_values: ["User 01 (user01)", "User 02 (user02)"],
                    },
                    {
                        field_id: 6,
                        type: "sb",
                        label: "Static List",
                        values: [
                            {
                                id: "4",
                                label: "Value01",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                        formatted_values: ["Value01"],
                    },
                    {
                        field_id: 7,
                        type: "rb",
                        label: "Ugroups List",
                        values: [
                            {
                                id: "101_3",
                                uri: "user_groups/101_3",
                                label: "Membres du projet",
                                users_uri: "user_groups/101_3/users",
                                short_name: "project_members",
                                key: "ugroup_project_members_name_key",
                            },
                        ],
                        formatted_values: ["Membres du projet"],
                    },
                    {
                        field_id: 8,
                        type: "cb",
                        label: "Checkbox List",
                        values: [
                            {
                                id: "15",
                                label: "MulitValue01",
                                color: null,
                                tlp_color: null,
                            },
                            {
                                id: "16",
                                label: "MulitValue02",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                        formatted_values: ["MulitValue01", "MulitValue02"],
                    },
                    {
                        field_id: 9,
                        type: "tbl",
                        label: "Open List",
                        bind_value_objects: [
                            {
                                id: "1",
                                label: "azerty",
                            },
                            {
                                id: "12548",
                                label: "OpenValue01",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                        formatted_open_values: ["azerty", "OpenValue01"],
                    },
                    {
                        field_id: 10,
                        type: "perm",
                        label: "Permissions",
                        granted_groups: ["membres_projet", "newgroup"],
                        granted_groups_ids: ["101_3", "105"],
                        formatted_granted_ugroups: ["Membres du projet", "newgroup"],
                    },
                    {
                        field_id: 11,
                        type: "ttmstepexec",
                        label: "Test Execution",
                        value: {
                            steps: [
                                {
                                    id: 13,
                                    description: "01",
                                    description_format: "text",
                                    expected_results: "01",
                                    expected_results_format: "text",
                                    rank: 1,
                                    status: "passed",
                                },
                                {
                                    id: 14,
                                    description: "This is text",
                                    description_format: "text",
                                    expected_results: "text\nwith\nnewlines",
                                    expected_results_format: "text",
                                    rank: 2,
                                    status: null,
                                },
                                {
                                    id: 15,
                                    description: "<p>This is HTML</p>",
                                    description_format: "html",
                                    expected_results:
                                        "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                                    expected_results_format: "html",
                                    rank: 3,
                                    status: "blocked",
                                },
                            ],
                            steps_values: ["passed", null, "blocked"],
                        },
                    },
                    {
                        field_id: 12,
                        label: "Links",
                        links: [
                            {
                                id: 359,
                                html_url: "/plugins/tracker/?aid=359",
                                title: "Linked artifact",
                                type: "_is_child",
                                is_linked_artifact_part_of_document: false,
                            },
                        ],
                        reverse_links: [
                            {
                                id: 3,
                                html_url: "/plugins/tracker/?aid=3",
                                title: "Reverse linked artifact",
                                type: null,
                                is_linked_artifact_part_of_document: false,
                            },
                        ],
                        type: "art_link",
                    },
                ],
                containers: [
                    {
                        name: "Fieldset label",
                        values: [
                            {
                                field_id: 2,
                                is_time_displayed: false,
                                label: "My Other Date",
                                type: "date",
                                value: "2021-07-01T00:00:00+02:00",
                            },
                            {
                                field_id: 1,
                                is_time_displayed: true,
                                label: "My Date",
                                type: "date",
                                value: "2021-07-30T15:56:09+02:00",
                            },
                        ],
                        containers: [],
                    },
                ],
            },
        ]);
    });
});
