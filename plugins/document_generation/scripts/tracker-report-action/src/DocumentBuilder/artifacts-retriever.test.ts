/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { ArtifactReportResponse, TrackerDefinition } from "./artifacts-retriever";
import { retrieveReportArtifacts } from "./artifacts-retriever";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("artifacts-retriever", () => {
    it("retrieves artifacts from a report with additional information", async () => {
        const recursive_get_spy = jest.spyOn(tlp_fetch, "recursiveGet");
        const artifacts_report_response: ArtifactReportResponse[] = [
            {
                id: 74,
                title: null,
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
                        type: "sb",
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
                    },
                    {
                        field_id: 6,
                        type: "sb",
                        label: "Static List",
                        values: [
                            {
                                id: 4,
                                label: "Value01",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                    },
                    {
                        field_id: 7,
                        type: "sb",
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
                ],
            },
        ];
        recursive_get_spy.mockResolvedValue(artifacts_report_response);

        const tracker_definition_response: TrackerDefinition = {
            fields: [
                { field_id: 2, type: "date", is_time_displayed: false },
                { field_id: 4, type: "fieldset", label: "Fieldset label" },
                { field_id: 5, type: "sb" },
                { field_id: 6, type: "sb" },
                { field_id: 7, type: "sb" },
            ],
            structure: [
                { id: 3, content: null },
                { id: 5, content: null },
                { id: 6, content: null },
                { id: 7, content: null },
                {
                    id: 4,
                    content: [
                        { id: 2, content: null },
                        { id: 1, content: null },
                    ],
                },
            ],
        };
        mockFetchSuccess(jest.spyOn(tlp_fetch, "get"), {
            return_json: tracker_definition_response,
        });

        const artifacts = await retrieveReportArtifacts(123, 852, false);
        expect(artifacts).toStrictEqual([
            {
                id: 74,
                title: null,
                values: [
                    {
                        field_id: 3,
                        label: "Some String",
                        type: "string",
                        value: null,
                    },
                    {
                        field_id: 5,
                        type: "sb",
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
                        field_id: 6,
                        type: "sb",
                        label: "Static List",
                        values: [
                            {
                                id: 4,
                                label: "Value01",
                                color: null,
                                tlp_color: null,
                            },
                        ],
                        formatted_values: ["Value01"],
                    },
                    {
                        field_id: 7,
                        type: "sb",
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
