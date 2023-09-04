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

import { getReportArtifacts } from "./rest-querier";

import type {
    ArtifactFromReport,
    ArtifactResponse,
    getTestManagementExecution,
} from "@tuleap/plugin-docgen-docx";
import { retrieveArtifactsStructure, retrieveTrackerStructure } from "@tuleap/plugin-docgen-docx";

export async function retrieveReportArtifacts(
    tracker_id: number,
    report_id: number,
    report_has_changed: boolean,
    get_test_execution: typeof getTestManagementExecution,
): Promise<ReadonlyArray<ArtifactFromReport>> {
    const tracker_structure_promise = retrieveTrackerStructure(tracker_id);
    const report_artifacts: ArtifactResponse[] = await getReportArtifacts(
        report_id,
        report_has_changed,
    );

    const tracker_structure = await tracker_structure_promise;

    return retrieveArtifactsStructure(
        new Map([[tracker_id, tracker_structure]]),
        report_artifacts,
        get_test_execution,
    );
}
