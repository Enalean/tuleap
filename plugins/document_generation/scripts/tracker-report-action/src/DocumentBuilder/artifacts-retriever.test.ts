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

import { describe, it, expect, vi } from "vitest";
import * as rest_querier from "./rest-querier";
import type {
    ArtifactFromReport,
    ArtifactResponse,
    TrackerStructure,
} from "@tuleap/plugin-docgen-docx";
import * as docgen_docx from "@tuleap/plugin-docgen-docx";
import { retrieveReportArtifacts } from "./artifacts-retriever";

describe("artifacts-retriever", () => {
    it("retrieves artifacts from a report with additional information from tracker structure", async () => {
        const get_report_artifacts_spy = vi.spyOn(rest_querier, "getReportArtifacts");
        const artifacts_report_response: ArtifactResponse[] = [
            {
                id: 74,
                title: null,
                xref: "bug #74",
                html_url: "/plugins/tracker/?aid=74",
            } as ArtifactResponse,
        ];
        get_report_artifacts_spy.mockResolvedValue(artifacts_report_response);

        const tracker_structure: TrackerStructure = {
            fields: new Map([[2, { field_id: 2, type: "date", is_time_displayed: false }]]),
            disposition: [{ id: 2, content: null }],
        };
        vi.spyOn(docgen_docx, "retrieveTrackerStructure").mockResolvedValue(tracker_structure);

        const artifacts_structure = [{ id: 74 } as ArtifactFromReport];
        vi.spyOn(docgen_docx, "retrieveArtifactsStructure").mockResolvedValue(artifacts_structure);

        const get_test_exec = vi.fn();

        await expect(
            retrieveReportArtifacts(123, 852, false, get_test_exec)
        ).resolves.toStrictEqual(artifacts_structure);
    });
});
