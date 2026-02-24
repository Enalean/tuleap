/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { describe, it, vi, expect } from "vitest";
import * as rest_querier from "./api/rest-querier";
import { okAsync } from "neverthrow";
import * as fs from "node:fs";
import * as path from "node:path";
import { downloadData } from "./download-data";
import type { ReportArtifact, TrackerStructure } from "./api/rest-querier";
import type { TrackerWithProjectAndColor } from "@tuleap/plugin-tracker-rest-api-types";

function buildReportArtifact(id: number): ReportArtifact {
    return {
        id,
        tracker: {} as TrackerWithProjectAndColor,
        values: [],
    };
}

describe("downloadData", () => {
    it("formats the ReportData according to the payload with only first level", async () => {
        const getReportArtifacts = vi.spyOn(rest_querier, "getReportArtifacts").mockReturnValue(
            okAsync(
                JSON.parse(
                    fs.readFileSync(path.resolve(__dirname, "_fixtures/getReportArtifacts.json"), {
                        encoding: "utf-8",
                    }),
                ),
            ),
        );
        const getTrackerStructure = vi.spyOn(rest_querier, "getTrackerStructure").mockReturnValue(
            okAsync(
                JSON.parse(
                    fs.readFileSync(path.resolve(__dirname, "_fixtures/getTrackerStructure.json"), {
                        encoding: "utf-8",
                    }),
                ),
            ),
        );

        const result = await downloadData({
            first_level: {
                tracker_id: 82,
                report_id: 224,
                artifact_link_types: [],
                all_columns: true,
            },
        });

        expect(getReportArtifacts).toHaveBeenCalledWith(224, undefined, true);
        expect(getTrackerStructure).toHaveBeenCalledWith(82);
        expect(result.isOk()).toBe(true);
        const report_data = result.unwrapOr(null);
        expect(report_data?.first_level.artifacts.length).toBe(10);
        expect(report_data?.first_level.artifacts[0]).toStrictEqual({
            id: 211,
            values: [
                {
                    label: "string_field",
                    name: "string_field",
                    type: "string",
                    value: "Hello World!",
                },
                {
                    label: "static_multiselect",
                    name: "static_multiselect",
                    type: "msb",
                    value: {
                        type: "static",
                        value: [
                            {
                                label: "g",
                            },
                        ],
                    },
                },
                {
                    label: "static_radio",
                    name: "static_radio",
                    type: "rb",
                    value: {
                        type: "static",
                        value: [
                            {
                                label: "e",
                            },
                        ],
                    },
                },
                {
                    label: "int_field",
                    name: "int_field",
                    type: "int",
                    value: 12,
                },
                {
                    label: "file_upload",
                    name: "file_upload",
                    type: "other",
                    value: "",
                },
                {
                    label: "static_select",
                    name: "static_select",
                    type: "sb",
                    value: {
                        type: "static",
                        value: [],
                    },
                },
                {
                    label: "static_openlist",
                    name: "static_openlist",
                    type: "tbl",
                    value: {
                        type: "static",
                        value: [
                            {
                                label: "1",
                            },
                        ],
                    },
                },
                {
                    label: "step_definition",
                    name: "step_definition",
                    type: "other",
                    value: "",
                },
                {
                    label: "static_checkbox",
                    name: "static_checkbox",
                    type: "cb",
                    value: {
                        type: "static",
                        value: [
                            {
                                label: "j",
                            },
                            {
                                label: "l",
                            },
                        ],
                    },
                },
                {
                    label: "per_tracker_id",
                    name: "per_tracker_id",
                    type: "atid",
                    value: 1,
                },
                {
                    label: "artifact_id",
                    name: "artifact_id",
                    type: "aid",
                    value: 211,
                },
                {
                    label: "submitted_on",
                    name: "submitted_on",
                    type: "subon",
                    value: new Date("2025-01-20T16:35:26+01:00"),
                },
                {
                    label: "string_field_2",
                    name: "string_field_2",
                    type: "string",
                    value: "**extemporizing** art #210",
                },
                {
                    label: "Artifact links",
                    name: "artifact_links",
                    type: "art_link",
                    forward: [
                        {
                            nature: null,
                            target: 9,
                        },
                        {
                            nature: "_covered_by",
                            target: 33,
                        },
                        {
                            nature: null,
                            target: 318,
                        },
                    ],
                    reverse: [
                        {
                            nature: "_covered_by",
                            target: 8,
                        },
                        {
                            nature: "_covered_by",
                            target: 261,
                        },
                    ],
                },
                {
                    label: "user_select",
                    name: "user_select",
                    type: "sb",
                    value: {
                        type: "users",
                        value: [
                            {
                                display_name: "Fred Bob (fred)",
                                username: "fred",
                            },
                        ],
                    },
                },
                {
                    label: "user_radio",
                    name: "user_radio",
                    type: "rb",
                    value: {
                        type: "users",
                        value: [
                            {
                                display_name: "Alice (alice)",
                                username: "alice",
                            },
                        ],
                    },
                },
                {
                    label: "user_multiselect",
                    name: "user_multiselect",
                    type: "msb",
                    value: {
                        type: "users",
                        value: [
                            {
                                display_name: "Site Administrator (admin)",
                                username: "admin",
                            },
                            {
                                display_name: "Fred Bob (fred)",
                                username: "fred",
                            },
                        ],
                    },
                },
                {
                    label: "user_checkbox",
                    name: "user_checkbox",
                    type: "cb",
                    value: {
                        type: "users",
                        value: [
                            {
                                display_name: "Site Administrator (admin)",
                                username: "admin",
                            },
                            {
                                display_name: "Fred Bob (fred)",
                                username: "fred",
                            },
                        ],
                    },
                },
                {
                    label: "float_field",
                    name: "float_field",
                    type: "float",
                    value: 2.56,
                },
                {
                    label: "last_update_date",
                    name: "last_update_date",
                    type: "lud",
                    value: new Date("2026-02-09T12:27:40+01:00"),
                },
                {
                    label: "date_field",
                    name: "date_field",
                    type: "date",
                    with_time: false,
                    value: new Date("2026-02-03T00:00:00+01:00"),
                },
                {
                    label: "user_openlist",
                    name: "user_openlist",
                    type: "tbl",
                    value: {
                        type: "users",
                        value: [
                            {
                                display_name: "john@example.com",
                                username: "john@example.com",
                            },
                            {
                                display_name: "Fred Bob (fred)",
                                username: "fred",
                            },
                        ],
                    },
                },
                {
                    label: "user_group_checkbox",
                    name: "user_group_checkbox",
                    type: "cb",
                    value: {
                        type: "ugroups",
                        value: [
                            {
                                key: "ugroup_project_members_name_key",
                                label: "Project members",
                            },
                            {
                                key: "ugroup_project_admins_name_key",
                                label: "Project administrators",
                            },
                            {
                                key: "ugroup_authenticated_users_name_key",
                                label: "Registered and restricted users",
                            },
                        ],
                    },
                },
                {
                    label: "submitted_by",
                    name: "submitted_by",
                    type: "subby",
                    value: {
                        display_name: "Fred Bob (fred)",
                        username: "fred",
                    },
                },
                {
                    label: "user_group_radio",
                    name: "user_group_radio",
                    type: "rb",
                    value: {
                        type: "ugroups",
                        value: [
                            {
                                key: "ugroup_project_admins_name_key",
                                label: "Project administrators",
                            },
                        ],
                    },
                },
                {
                    label: "last_update_by",
                    name: "last_update_by",
                    type: "luby",
                    value: {
                        display_name: "Fred Bob (fred)",
                        username: "fred",
                    },
                },
                {
                    label: "user_group_multiselect",
                    name: "user_group_multiselect",
                    type: "msb",
                    value: {
                        type: "ugroups",
                        value: [
                            {
                                key: "ugroup_project_members_name_key",
                                label: "Project members",
                            },
                            {
                                key: "ugroup_project_admins_name_key",
                                label: "Project administrators",
                            },
                        ],
                    },
                },
                {
                    label: "date_time_field",
                    name: "date_time_field",
                    type: "date",
                    with_time: true,
                    value: new Date("2026-02-19T17:08:00+01:00"),
                },
                {
                    label: "user_group_openlist",
                    name: "user_group_openlist",
                    type: "tbl",
                    value: {
                        type: "ugroups",
                        value: [
                            {
                                key: "ugroup_project_members_name_key",
                                label: "Project members",
                            },
                        ],
                    },
                },
                {
                    label: "text_field",
                    name: "text_field",
                    type: "text",
                    value: "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam non sagittis elit. Phasellus eget neque nec ipsum semper tristique. Maecenas sagittis lacus vel urna condimentum mattis. Aliquam fringilla, tortor eu eleifend cursus, augue arcu gravida sapien, sit amet elementum ex lorem vitae eros. Quisque viverra sodales orci vitae interdum. Vivamus viverra auctor magna. Suspendisse vitae libero interdum, suscipit lectus a, vestibulum sem.</p>",
                },
                {
                    label: "permissions",
                    name: "permissions",
                    type: "perm",
                    value: ["registered_users", "Custom"],
                },
                {
                    label: "text_field_2",
                    name: "text_field_2",
                    type: "text",
                    commonmark: "# **Hello _World!_**\r\n\r\nSee art #213",
                    value: '<h1><strong>Hello <em>World!</em></strong></h1>\n<p>See <a href="https://tuleap-web.tuleap-aio-dev.docker/goto?key=art&amp;val=213&amp;group_id=102" title="Tracker Artifact" class="cross-reference">art #213</a></p>\n',
                },
                {
                    label: "user_group_select",
                    name: "user_group_select",
                    type: "sb",
                    value: {
                        type: "ugroups",
                        value: [
                            {
                                key: "ugroup_project_members_name_key",
                                label: "Project members",
                            },
                        ],
                    },
                },
                {
                    label: "cross_references",
                    name: "cross_references",
                    type: "cross",
                    value: [
                        {
                            reference: "test_1 #33",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=test_1&val=33&group_id=106",
                        },
                        {
                            reference: "task #318",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=task&val=318&group_id=102",
                        },
                        {
                            reference: "story #9",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=story&val=9&group_id=102",
                        },
                        {
                            reference: "art #213",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=art&val=213&group_id=102",
                        },
                        {
                            reference: "art #210",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=art&val=210&group_id=102",
                        },
                        {
                            reference: "all_fields_tracker #261",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=all_fields_tracker&val=261&group_id=102",
                        },
                        {
                            reference: "rel #8",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=rel&val=8&group_id=102",
                        },
                        {
                            reference: "all_fields_tracker #211",
                            url: "https://tuleap-web.tuleap-aio-dev.docker/goto?key=all_fields_tracker&val=211&group_id=102",
                        },
                    ],
                },
                {
                    label: "rank",
                    name: "rank",
                    type: "priority",
                    value: 249,
                },
                {
                    label: "computed",
                    name: "computed",
                    type: "computed",
                    value: null,
                },
            ],
        });
    });

    it("formats the ReportData according to the payload with all levels", async () => {
        const getReportArtifacts = vi
            .spyOn(rest_querier, "getReportArtifacts")
            .mockReturnValueOnce(okAsync([buildReportArtifact(123)]))
            .mockReturnValueOnce(okAsync([buildReportArtifact(124), buildReportArtifact(125)]))
            .mockReturnValueOnce(okAsync([buildReportArtifact(126), buildReportArtifact(127)]));
        const getTrackerStructure = vi
            .spyOn(rest_querier, "getTrackerStructure")
            .mockReturnValueOnce(okAsync({} as TrackerStructure))
            .mockReturnValueOnce(okAsync({} as TrackerStructure))
            .mockReturnValueOnce(okAsync({} as TrackerStructure));
        const getLinkedArtifacts = vi
            .spyOn(rest_querier, "getLinkedArtifacts")
            .mockReturnValueOnce(okAsync([{ id: 124 }]))
            .mockReturnValueOnce(okAsync([{ id: 126 }, { id: 127 }]));

        const result = await downloadData({
            first_level: {
                tracker_id: 82,
                report_id: 224,
                artifact_link_types: ["_is_child"],
                all_columns: false,
            },
            second_level: {
                tracker_id: 83,
                report_id: 225,
                artifact_link_types: ["_covered_by"],
                all_columns: false,
            },
            third_level: {
                tracker_id: 84,
                report_id: 226,
                all_columns: false,
            },
        });

        expect(getReportArtifacts).toHaveBeenCalledTimes(3);
        expect(getReportArtifacts).toHaveBeenNthCalledWith(1, 224, undefined, false);
        expect(getReportArtifacts).toHaveBeenNthCalledWith(2, 225, undefined, false);
        expect(getReportArtifacts).toHaveBeenNthCalledWith(3, 226, undefined, false);
        expect(getTrackerStructure).toHaveBeenCalledTimes(3);
        expect(getTrackerStructure).toHaveBeenNthCalledWith(1, 82);
        expect(getTrackerStructure).toHaveBeenNthCalledWith(2, 83);
        expect(getTrackerStructure).toHaveBeenNthCalledWith(3, 84);
        expect(getLinkedArtifacts).toHaveBeenCalledTimes(2);
        expect(getLinkedArtifacts).toHaveBeenNthCalledWith(1, 123, "_is_child");
        expect(getLinkedArtifacts).toHaveBeenNthCalledWith(2, 124, "_covered_by");
        expect(result.isOk()).toBe(true);
        const report_data = result.unwrapOr(null);
        expect(report_data?.first_level.artifacts.length).toBe(1);
        expect(report_data?.first_level.artifacts[0].id).toBe(123);
        expect(report_data?.second_level?.artifacts.length).toBe(1);
        expect(report_data?.second_level?.artifacts[0].id).toBe(124);
        expect(report_data?.third_level?.artifacts.length).toBe(2);
        expect(report_data?.third_level?.artifacts).toStrictEqual([
            { id: 126, values: [] },
            { id: 127, values: [] },
        ]);
    });
});
