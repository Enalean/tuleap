/*
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
import * as artifact_retriever from "./artifacts-retriever";
import { createExportDocument } from "./create-export-document";
import type {
    ArtifactFromReport,
    ArtifactReportResponseStepDefinitionFieldValue,
    ArtifactReportResponseUnknownFieldValue,
} from "@tuleap/plugin-docgen-docx/src";

describe("Create ArtifactValues Collection", () => {
    it("Transforms json content into a collection", async () => {
        const report_artifacts: ArtifactFromReport[] = [
            {
                id: 1001,
                xref: "tracker_shortname #1001",
                title: "title01",
                values: [
                    {
                        field_id: 1,
                        type: "aid",
                        label: "Artifact Number",
                        value: 1001,
                    },
                    {
                        field_id: 2,
                        type: "whatever",
                        label: "What Ever",
                        value: 9999,
                    } as ArtifactReportResponseUnknownFieldValue,
                    {
                        field_id: 3,
                        type: "string",
                        label: "Title",
                        value: "title01",
                    },
                    {
                        field_id: 4,
                        type: "int",
                        label: "Capacity",
                        value: 5,
                    },
                    {
                        field_id: 5,
                        type: "float",
                        label: "Effort",
                        value: 1.5,
                    },
                    {
                        field_id: 6,
                        type: "atid",
                        label: "Per tracker ID",
                        value: 1,
                    },
                    {
                        field_id: 7,
                        type: "priority",
                        label: "Rank",
                        value: 50,
                    },
                    {
                        field_id: 8,
                        type: "computed",
                        label: "Computed",
                        value: null,
                        manual_value: 10,
                        is_autocomputed: false,
                    },
                    {
                        field_id: 9,
                        type: "subon",
                        label: "Submitted On",
                        value: "2020-12-28T09:55:55+00:00",
                        is_time_displayed: true,
                    },
                    {
                        field_id: 10,
                        type: "lud",
                        label: "Last Update Date",
                        value: "2021-07-30T15:56:09+00:00",
                        is_time_displayed: false,
                    },
                    {
                        field_id: 11,
                        type: "date",
                        label: "Closed Date",
                        value: null,
                        is_time_displayed: false,
                    },
                    {
                        field_id: 13,
                        type: "text",
                        label: "Description",
                        value: "Some long description in art #1001",
                        format: "text",
                    },
                    {
                        field_id: 14,
                        type: "file",
                        label: "Attachments",
                        file_descriptions: [
                            {
                                id: 1,
                                submitted_by: 101,
                                description: "",
                                name: "file01.jpg",
                                size: 254,
                                type: "image/jpg",
                                html_url: "/plugins/tracker/attachments/file01.jpg",
                                html_preview_url: "/plugins/tracker/attachments/preview/file01.jpg",
                                uri: "artifact_files/1",
                            },
                            {
                                id: 2,
                                submitted_by: 101,
                                description: "",
                                name: "file02.jpg",
                                size: 5841,
                                type: "image/jpg",
                                html_url: "/plugins/tracker/attachments/file02.jpg",
                                html_preview_url: "/plugins/tracker/attachments/preview/file02.jpg",
                                uri: "artifact_files/2",
                            },
                        ],
                    },
                    {
                        field_id: 15,
                        type: "subby",
                        label: "Submitted By",
                        value: {
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
                    },
                    {
                        field_id: 16,
                        type: "luby",
                        label: "Last Update By",
                        value: {
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
                    },
                    {
                        field_id: 17,
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
                        ],
                        formatted_values: ["User 01 (user01)"],
                    },
                    {
                        field_id: 18,
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
                        field_id: 19,
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
                        field_id: 20,
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
                        field_id: 21,
                        type: "tbl",
                        label: "Open List",
                        bind_value_objects: [
                            {
                                id: "12549",
                                label: "OpenValue02",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                        formatted_open_values: ["OpenValue02"],
                    },
                    {
                        field_id: 22,
                        type: "perm",
                        label: "Permissions",
                        granted_groups: ["membres_projet", "newgroup"],
                        granted_groups_ids: ["101_3", "105"],
                        formatted_granted_ugroups: ["Membres du projet", "newgroup"],
                    },
                    {
                        field_id: 23,
                        type: "cross",
                        label: "References",
                        value: [
                            {
                                ref: "task #359",
                                url: "https://example.com/goto?key=task&val=359&group_id=101",
                                direction: "out",
                            },
                            {
                                ref: "git #repo01/1cc07fe8c3b59c0c6af414672e89fe27e3fed41d",
                                url: "https://example.com/goto?key=git&val=repo01%2F1cc07fe8c3b59c0c6af414672e89fe27e3fed41d&group_id=101",
                                direction: "in",
                            },
                            {
                                ref: "git #testRefs/47870155006fa81640f5b9646fddf6ef33c6680a",
                                url: "https://example.com/goto?key=git&val=testRefs%2F47870155006fa81640f5b9646fddf6ef33c6680a&group_id=101",
                                direction: "in",
                            },
                            {
                                ref: "rel #3",
                                url: "https://example.com/goto?key=rel&val=3&group_id=101",
                                direction: "in",
                            },
                        ],
                    },
                    {
                        field_id: 24,
                        type: "ttmstepdef",
                        label: "Step Definition",
                        value: [
                            {
                                id: 50,
                                description: "01",
                                description_format: "text",
                                expected_results: "01",
                                expected_results_format: "text",
                                rank: 1,
                            },
                            {
                                id: 51,
                                description: "<p>04</p>\n",
                                description_format: "html",
                                commonmark_description: "04",
                                expected_results: "<p>04</p>\n",
                                expected_results_format: "html",
                                commonmark_expected_results: "04",
                                rank: 2,
                            },
                        ],
                    } as ArtifactReportResponseStepDefinitionFieldValue,
                    {
                        field_id: 25,
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
                        field_id: 26,
                        type: "art_link",
                        label: "Links",
                        links: [
                            {
                                type: "_is_child",
                                title: "Linked artifact",
                                id: 359,
                                is_linked_artifact_part_of_document: true,
                                html_url: "/path/to/26",
                            },
                        ],
                        reverse_links: [
                            {
                                type: null,
                                title: "Reverse linked artifact",
                                id: 3,
                                is_linked_artifact_part_of_document: false,
                                html_url: "/path/to/3",
                            },
                        ],
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        values: [],
                        containers: [
                            {
                                name: "Sub details",
                                values: [
                                    {
                                        field_id: 12,
                                        type: "string",
                                        label: "A detail",
                                        value: "Value in art #1001",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
            {
                id: 1002,
                xref: "tracker_shortname #1002",
                title: "",
                values: [
                    {
                        field_id: 1,
                        type: "aid",
                        label: "Artifact Number",
                        value: 1002,
                    },
                    {
                        field_id: 3,
                        type: "string",
                        label: "Title",
                        value: "",
                    },
                    {
                        field_id: 4,
                        type: "int",
                        label: "Capacity",
                        value: 2,
                    },
                    {
                        field_id: 5,
                        type: "float",
                        label: "Effort",
                        value: 2.5,
                    },
                    {
                        field_id: 6,
                        type: "atid",
                        label: "Per tracker ID",
                        value: 2,
                    },
                    {
                        field_id: 7,
                        type: "priority",
                        label: "Rank",
                        value: 51,
                    },
                    {
                        field_id: 8,
                        type: "computed",
                        label: "Computed",
                        value: 10,
                        manual_value: null,
                        is_autocomputed: true,
                    },
                    {
                        field_id: 9,
                        type: "subon",
                        label: "Submitted On",
                        value: "2020-12-29T09:55:55+00:00",
                        is_time_displayed: true,
                    },
                    {
                        field_id: 10,
                        type: "lud",
                        label: "Last Update Date",
                        value: "2021-07-29T15:56:09+00:00",
                        is_time_displayed: false,
                    },
                    {
                        field_id: 11,
                        type: "date",
                        label: "Closed Date",
                        value: null,
                        is_time_displayed: false,
                    },
                    {
                        field_id: 13,
                        type: "text",
                        label: "Description",
                        value: "<p>Some long description in art #1002</p>",
                        format: "html",
                    },
                    {
                        field_id: 14,
                        type: "file",
                        label: "Attachments",
                        file_descriptions: [],
                    },
                    {
                        field_id: 15,
                        type: "subby",
                        label: "Submitted By",
                        value: {
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
                    },
                    {
                        field_id: 16,
                        type: "luby",
                        label: "Last Update By",
                        value: {
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
                    },
                    {
                        field_id: 17,
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
                        ],
                        formatted_values: ["User 01 (user01)", "User 02 (user02)"],
                    },
                    {
                        field_id: 18,
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
                        field_id: 19,
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
                        field_id: 20,
                        type: "cb",
                        label: "Checkbox List",
                        values: [
                            {
                                id: "17",
                                label: "MulitValue03",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                        formatted_values: ["MulitValue03"],
                    },
                    {
                        field_id: 21,
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
                        field_id: 22,
                        type: "perm",
                        label: "Permissions",
                        granted_groups: [],
                        granted_groups_ids: [],
                        formatted_granted_ugroups: [],
                    },
                    {
                        field_id: 23,
                        type: "cross",
                        label: "References",
                        value: [],
                    },
                    {
                        field_id: 24,
                        type: "ttmstepdef",
                        label: "Step Definition",
                        value: [],
                    },
                    {
                        field_id: 25,
                        type: "ttmstepexec",
                        label: "Test Execution",
                        value: null,
                    },
                    {
                        field_id: 26,
                        type: "art_link",
                        label: "Links",
                        links: [],
                        reverse_links: [],
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        values: [],
                        containers: [
                            {
                                name: "Sub details",
                                values: [
                                    {
                                        field_id: 12,
                                        type: "string",
                                        label: "A detail",
                                        value: "Value in art #1002",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
        ];
        vi.spyOn(artifact_retriever, "retrieveReportArtifacts").mockResolvedValueOnce(
            report_artifacts,
        );

        const report = await createExportDocument(
            1,
            false,
            "report_name",
            123,
            "tracker_shortname",
            { locale: "en-US", timezone: "UTC" },
            "https://example.com/",
            [
                {
                    reverse_label: "Parent",
                    forward_label: "Enfant",
                    shortname: "_is_child",
                    is_system: true,
                    is_visible: true,
                },
            ],
        );

        expect(report.name).toBe("tracker_shortname - report_name");

        const collection = report.artifacts;

        expect(collection).toStrictEqual([
            {
                id: 1001,
                title: "tracker_shortname #1001 - title01",
                short_title: "title01",
                fields: [
                    {
                        content_length: "short",
                        field_name: "Artifact Number",
                        field_value: "1001",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Title",
                        field_value: "title01",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Capacity",
                        field_value: "5",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Effort",
                        field_value: "1.5",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Per tracker ID",
                        field_value: "1",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Rank",
                        field_value: "50",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Computed",
                        field_value: "10",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Submitted On",
                        field_value: "12/28/2020 9:55:55 AM",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Last Update Date",
                        field_value: "7/30/2021",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Closed Date",
                        field_value: "",
                        value_type: "string",
                    },
                    {
                        content_length: "long",
                        content_format: "plaintext",
                        field_name: "Description",
                        field_value: "Some long description in art #1001",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Attachments",
                        field_value: [
                            {
                                link_label: "file01.jpg",
                                link_url:
                                    "https://example.com/plugins/tracker/attachments/file01.jpg",
                            },
                            {
                                link_label: "file02.jpg",
                                link_url:
                                    "https://example.com/plugins/tracker/attachments/file02.jpg",
                            },
                        ],
                        value_type: "links",
                    },
                    {
                        content_length: "short",
                        field_name: "Submitted By",
                        field_value: "User 01 (user01)",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Last Update By",
                        field_value: "User 02 (user02)",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "User List",
                        field_value: "User 01 (user01)",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Static List",
                        field_value: "Value01",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Ugroups List",
                        field_value: "Membres du projet",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Checkbox List",
                        field_value: "MulitValue01, MulitValue02",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Open List",
                        field_value: "OpenValue02",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Permissions",
                        field_value: "Membres du projet, newgroup",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "References",
                        field_value: [
                            {
                                link_label: "task #359",
                                link_url: "https://example.com/goto?key=task&val=359&group_id=101",
                            },
                            {
                                link_label: "git #repo01/1cc07fe8c3b59c0c6af414672e89fe27e3fed41d",
                                link_url:
                                    "https://example.com/goto?key=git&val=repo01%2F1cc07fe8c3b59c0c6af414672e89fe27e3fed41d&group_id=101",
                            },
                            {
                                link_label:
                                    "git #testRefs/47870155006fa81640f5b9646fddf6ef33c6680a",
                                link_url:
                                    "https://example.com/goto?key=git&val=testRefs%2F47870155006fa81640f5b9646fddf6ef33c6680a&group_id=101",
                            },
                            {
                                link_label: "rel #3",
                                link_url: "https://example.com/goto?key=rel&val=3&group_id=101",
                            },
                        ],
                        value_type: "links",
                    },
                    {
                        content_length: "blockttmstepdef",
                        field_name: "Step Definition",
                        value_type: "string",
                        steps: [
                            {
                                description: "01",
                                description_format: "plaintext",
                                expected_results: "01",
                                expected_results_format: "plaintext",
                                rank: 1,
                            },
                            {
                                description: "<p>04</p>\n",
                                description_format: "html",
                                expected_results: "<p>04</p>\n",
                                expected_results_format: "html",
                                rank: 2,
                            },
                        ],
                    },
                    {
                        content_length: "blockttmstepexec",
                        field_name: "Test Execution",
                        value_type: "string",
                        steps: [
                            {
                                description: "01",
                                description_format: "plaintext",
                                expected_results: "01",
                                expected_results_format: "plaintext",
                                rank: 1,
                                status: "passed",
                            },
                            {
                                description: "This is text",
                                description_format: "plaintext",
                                expected_results: "text\nwith\nnewlines",
                                expected_results_format: "plaintext",
                                rank: 2,
                                status: "notrun",
                            },
                            {
                                description: "<p>This is HTML</p>",
                                description_format: "html",
                                expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                                expected_results_format: "html",
                                rank: 3,
                                status: "blocked",
                            },
                        ],
                        steps_values: ["passed", "notrun", "blocked"],
                    },
                    {
                        content_length: "artlinktable",
                        field_name: "Links",
                        value_type: "string",
                        links: [
                            {
                                artifact_id: 359,
                                html_url: new URL("https://example.com/path/to/26"),
                                is_linked_artifact_part_of_document: true,
                                title: "Linked artifact",
                                type: "Enfant",
                            },
                        ],
                        reverse_links: [
                            {
                                artifact_id: 3,
                                html_url: new URL("https://example.com/path/to/3"),
                                is_linked_artifact_part_of_document: false,
                                title: "Reverse linked artifact",
                                type: "",
                            },
                        ],
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        fields: [],
                        containers: [
                            {
                                name: "Sub details",
                                fields: [
                                    {
                                        content_length: "short",
                                        field_name: "A detail",
                                        field_value: "Value in art #1001",
                                        value_type: "string",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
            {
                id: 1002,
                title: "tracker_shortname #1002",
                short_title: "tracker_shortname #1002",
                fields: [
                    {
                        content_length: "short",
                        field_name: "Artifact Number",
                        field_value: "1002",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Title",
                        field_value: "",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Capacity",
                        field_value: "2",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Effort",
                        field_value: "2.5",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Per tracker ID",
                        field_value: "2",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Rank",
                        field_value: "51",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Computed",
                        field_value: "10",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Submitted On",
                        field_value: "12/29/2020 9:55:55 AM",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Last Update Date",
                        field_value: "7/29/2021",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Closed Date",
                        field_value: "",
                        value_type: "string",
                    },
                    {
                        content_length: "long",
                        content_format: "html",
                        field_name: "Description",
                        field_value: "<p>Some long description in art #1002</p>",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Attachments",
                        field_value: [],
                        value_type: "links",
                    },
                    {
                        content_length: "short",
                        field_name: "Submitted By",
                        field_value: "User 02 (user02)",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Last Update By",
                        field_value: "User 02 (user02)",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "User List",
                        field_value: "User 01 (user01), User 02 (user02)",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Static List",
                        field_value: "Value01",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Ugroups List",
                        field_value: "Membres du projet",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Checkbox List",
                        field_value: "MulitValue03",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Open List",
                        field_value: "azerty, OpenValue01",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "Permissions",
                        field_value: "",
                        value_type: "string",
                    },
                    {
                        content_length: "short",
                        field_name: "References",
                        field_value: [],
                        value_type: "links",
                    },
                    {
                        content_length: "blockttmstepdef",
                        field_name: "Step Definition",
                        value_type: "string",
                        steps: [],
                    },
                    {
                        content_length: "artlinktable",
                        field_name: "Links",
                        value_type: "string",
                        links: [],
                        reverse_links: [],
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        fields: [],
                        containers: [
                            {
                                name: "Sub details",
                                fields: [
                                    {
                                        content_length: "short",
                                        field_name: "A detail",
                                        field_value: "Value in art #1002",
                                        value_type: "string",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
        ]);
    });
});
