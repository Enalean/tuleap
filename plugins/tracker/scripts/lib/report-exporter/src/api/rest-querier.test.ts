/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import * as tlp from "@tuleap/tlp-fetch";
import { getLinkedArtifactsOld, getReportArtifactsOld } from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { ArtifactForCrossReportDocGen } from "../type";

vi.mock("@tuleap/tlp-fetch");

describe("API querier", () => {
    describe("getReportArtifacts", () => {
        it("Given a report id, Then it will get the artifact matching the report, and the report in session if needed", async () => {
            const report_id = 101;
            const report_has_changed = true;
            const tlpRecursiveGet = vi.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactForCrossReportDocGen[] = [
                {
                    id: 74,
                } as ArtifactForCrossReportDocGen,
            ];
            mockFetchSuccess(tlpRecursiveGet, {
                return_json: {
                    artifacts_report_response,
                },
            });

            await getReportArtifactsOld(report_id, report_has_changed, 136, false);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/tracker_reports/101/artifacts", {
                params: {
                    limit: 50,
                    values: "from_table_renderer",
                    with_unsaved_changes: true,
                    table_renderer_id: 136,
                },
            });
        });

        it("Fetch all columns when asked", async () => {
            const tlpRecursiveGet = vi.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactForCrossReportDocGen[] = [
                {
                    id: 74,
                } as ArtifactForCrossReportDocGen,
            ];
            mockFetchSuccess(tlpRecursiveGet, {
                return_json: {
                    artifacts_report_response,
                },
            });

            await getReportArtifactsOld(101, true, 136, true);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/tracker_reports/101/artifacts", {
                params: {
                    limit: 50,
                    values: "all",
                    with_unsaved_changes: true,
                    table_renderer_id: 136,
                },
            });
        });
    });
    describe("getLinkedArtifacts", () => {
        it("Given an artifact id and a link type, Then it will get the linked artifacts with this type", async () => {
            const artifact_id = 101;
            const artifact_link_type = "_is_child";
            const tlpRecursiveGet = vi.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactForCrossReportDocGen[] = [
                {
                    id: 74,
                } as ArtifactForCrossReportDocGen,
            ];
            mockFetchSuccess(tlpRecursiveGet, {
                return_json: {
                    collection: artifacts_report_response,
                },
            });

            await getLinkedArtifactsOld(artifact_id, artifact_link_type);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/artifacts/101/linked_artifacts", {
                params: { limit: 10, direction: "forward", nature: "_is_child" },
            });
        });
    });
});
