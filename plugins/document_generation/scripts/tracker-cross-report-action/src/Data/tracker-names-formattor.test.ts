/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { formatTrackerNames } from "./tracker-names-formattor";
import type { OrganizedReportsData } from "../type";
import { TextCellWithMerges } from "../type";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";

describe("tracker-names-formattor", () => {
    it("Formats tracker names", (): void => {
        const first_level_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        first_level_artifact_representations_map.set(74, {
            id: 74,
            title: null,
            xref: "bug #74",
            tracker: { id: 14 },
            html_url: "/plugins/tracker/?aid=74",
            values: [
                {
                    field_id: 1,
                    type: "aid",
                    label: "Artifact ID",
                    value: 74,
                },
                {
                    field_id: 2,
                    type: "string",
                    label: "Field02",
                    value: "value02",
                },
                {
                    field_id: 3,
                    type: "sb",
                    label: "Assigned to",
                    values: [],
                },
                {
                    field_id: 4,
                    type: "art_link",
                    label: "Artifact links",
                    values: [],
                },
                {
                    field_id: 5,
                    type: "file",
                    label: "Attachment",
                    values: [],
                },
            ],
        } as ArtifactResponse);
        first_level_artifact_representations_map.set(4, {
            id: 4,
            title: null,
            xref: "bug #4",
            tracker: { id: 14 },
            html_url: "/plugins/tracker/?aid=4",
            values: [
                {
                    field_id: 1,
                    type: "aid",
                    label: "Artifact ID",
                    value: 4,
                },
                {
                    field_id: 2,
                    type: "string",
                    label: "Field02",
                    value: "value04",
                },
                {
                    field_id: 3,
                    type: "sb",
                    label: "Assigned to",
                    values: [],
                },
                {
                    field_id: 4,
                    type: "art_link",
                    label: "Artifact links",
                    values: [],
                },
                {
                    field_id: 5,
                    type: "file",
                    label: "Attachment",
                    values: [],
                },
            ],
        } as ArtifactResponse);

        const second_level_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        second_level_artifact_representations_map.set(4, {
            id: 75,
            title: null,
            xref: "bug #15",
            tracker: { id: 25 },
            html_url: "/plugins/tracker/?aid=75",
            values: [
                {
                    field_id: 1,
                    type: "aid",
                    label: "Artifact ID",
                    value: 75,
                },
            ],
        } as ArtifactResponse);

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                artifact_representations: first_level_artifact_representations_map,
                tracker_name: "tracker01",
            },
            second_level: {
                artifact_representations: second_level_artifact_representations_map,
                tracker_name: "tracker02",
            },
        };

        const formatted_tracker_names = formatTrackerNames(organized_reports_data);
        expect(formatted_tracker_names).toStrictEqual([
            new TextCellWithMerges("tracker01", 3),
            new TextCellWithMerges("tracker02", 1),
        ]);
    });
});
