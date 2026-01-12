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

import { recursiveGet } from "@tuleap/tlp-fetch";
import type { ArtifactForCrossReportDocGen, LinkedArtifactsResponse } from "../type";

export function getReportArtifacts(
    report_id: number,
    report_has_changed: boolean,
    table_renderer_id: number | undefined,
    all_columns: boolean,
): Promise<ArtifactForCrossReportDocGen[]> {
    const params: Record<string, number | string | boolean> = {
        values: all_columns ? "all" : "from_table_renderer",
        with_unsaved_changes: report_has_changed,
        limit: 50,
    };
    if (table_renderer_id !== undefined) {
        params.table_renderer_id = table_renderer_id;
    }
    return recursiveGet(`/api/v1/tracker_reports/${encodeURIComponent(report_id)}/artifacts`, {
        params,
    });
}

export function getLinkedArtifacts(
    artifact_id: number,
    artifact_link_type: string,
): Promise<LinkedArtifactsResponse[]> {
    return recursiveGet(`/api/v1/artifacts/${encodeURIComponent(artifact_id)}/linked_artifacts`, {
        params: {
            direction: "forward",
            nature: artifact_link_type,
            limit: 10,
        },
    });
}
