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
import * as report_field_label_extractor from "./report-fields-labels-extractor";
import { formatTrackerNames } from "./tracker-names-formattor";
import type { OrganizedReportsData, ArtifactForCrossReportDocGen } from "../type";
import { TextCellWithMerges } from "../type";

describe("tracker-names-formattor", () => {
    it("Formats tracker names", (): void => {
        const spy_extract_field_labels = vi.spyOn(
            report_field_label_extractor,
            "extractFieldsLabels",
        );
        spy_extract_field_labels.mockReturnValue(["Something"]);

        const first_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            new Map();
        first_level_artifact_representations_map.set(74, {} as ArtifactForCrossReportDocGen);
        first_level_artifact_representations_map.set(4, {} as ArtifactForCrossReportDocGen);

        const second_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            new Map();
        second_level_artifact_representations_map.set(4, {} as ArtifactForCrossReportDocGen);

        const third_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            new Map();
        third_level_artifact_representations_map.set(4, {} as ArtifactForCrossReportDocGen);

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                artifact_representations: first_level_artifact_representations_map,
                tracker_name: "tracker01",
                linked_artifacts: new Map(),
            },
            second_level: {
                artifact_representations: second_level_artifact_representations_map,
                tracker_name: "tracker02",
                linked_artifacts: new Map(),
            },
            third_level: {
                artifact_representations: third_level_artifact_representations_map,
                tracker_name: "tracker03",
            },
        };

        const formatted_tracker_names = formatTrackerNames(organized_reports_data);

        expect(spy_extract_field_labels).toHaveBeenCalledTimes(3);

        expect(formatted_tracker_names).toStrictEqual([
            new TextCellWithMerges("tracker01", 1),
            new TextCellWithMerges("tracker02", 1),
            new TextCellWithMerges("tracker03", 1),
        ]);
    });
});
