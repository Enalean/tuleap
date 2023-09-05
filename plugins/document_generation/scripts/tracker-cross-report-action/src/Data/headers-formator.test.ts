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

import { describe, it, expect, vi } from "vitest";
import * as tracker_names_formattor from "./tracker-names-formattor";
import * as reports_fields_labels_formator from "./reports-fields-labels-formator";
import type {
    OrganizedReportsData,
    ArtifactForCrossReportDocGen,
    OrganizedReportDataLevel,
} from "../type";
import { formatHeaders } from "./headers-formator";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { TextCellWithMerges } from "../type";

describe("headers-formator", () => {
    it("builds the headers TextCell", (): void => {
        const expected_tracker_names = [
            new TextCellWithMerges("Tracker01", 1),
            new TextCellWithMerges("Tracker02", 1),
        ];
        vi.spyOn(tracker_names_formattor, "formatTrackerNames").mockReturnValue(
            expected_tracker_names,
        );
        const expected_reports_fields_labels = [
            new TextCell("Artifact ID"),
            new TextCell("Field02"),
            new TextCell("Assigned to"),
            new TextCell("Artifact ID"),
        ];
        vi.spyOn(reports_fields_labels_formator, "formatReportsFieldsLabels").mockReturnValue(
            expected_reports_fields_labels,
        );

        const fake_organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: new Map<number, ArtifactForCrossReportDocGen>([
                    [1, { id: 1 } as ArtifactForCrossReportDocGen],
                ]),
            } as OrganizedReportDataLevel,
            second_level: {
                tracker_name: "Tracker02",
            } as OrganizedReportDataLevel,
        };

        const formatted_headers = formatHeaders(fake_organized_reports_data);
        expect(formatted_headers).toStrictEqual({
            tracker_names: expected_tracker_names,
            reports_fields_labels: expected_reports_fields_labels,
        });
    });
    it("throws an Error if organized data does not have any ArtifactResponse in first level", (): void => {
        const artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> = new Map();

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: artifact_representations_map,
                linked_artifacts: new Map(),
            },
        };

        expect(() => formatHeaders(organized_reports_data)).toThrowError(
            "This must not happen. Check must be done before.",
        );
    });
});
