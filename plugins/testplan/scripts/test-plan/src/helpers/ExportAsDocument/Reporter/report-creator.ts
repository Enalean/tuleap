/**
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

import type { VueGettextProvider } from "../../vue-gettext-provider";
import type { BacklogItem, Campaign, ExportDocument, GlobalExportProperties } from "../../../type";
import type {
    TrackerStructure,
    DateTimeLocaleInformation,
    ArtifactResponse,
} from "@tuleap/plugin-docgen-docx";
import {
    formatArtifact,
    getArtifacts,
    getTestManagementExecution,
    retrieveArtifactsStructure,
    retrieveTrackerStructure,
} from "@tuleap/plugin-docgen-docx";
import { memoize } from "./memoize";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import { getTraceabilityMatrix } from "./traceability-matrix-creator";

interface TrackerStructurePromiseTuple {
    readonly tracker_id: number;
    readonly tracker_structure_promise: Promise<TrackerStructure>;
}

export async function createExportReport(
    gettext_provider: VueGettextProvider,
    global_properties: GlobalExportProperties,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<ExportDocument> {
    const get_test_execution = memoize(getTestManagementExecution);

    const tracker_structure_promises_map: Map<number, TrackerStructurePromiseTuple> = new Map();
    const artifact_ids: Set<number> = new Set();
    for (const item of backlog_items) {
        artifact_ids.add(item.artifact.id);

        const tracker_id = item.artifact.tracker.id;
        if (tracker_structure_promises_map.has(tracker_id)) {
            continue;
        }
        tracker_structure_promises_map.set(tracker_id, {
            tracker_id,
            tracker_structure_promise: retrieveTrackerStructure(tracker_id),
        });
    }

    const tracker_structure_map: Map<number, TrackerStructure> = new Map();
    await limitConcurrencyPool(
        4,
        [...tracker_structure_promises_map.values()],
        async (tracker_structure_tuple: TrackerStructurePromiseTuple): Promise<void> => {
            tracker_structure_map.set(
                tracker_structure_tuple.tracker_id,
                await tracker_structure_tuple.tracker_structure_promise
            );
        }
    );

    const artifacts: ArtifactResponse[] = [...(await getArtifacts(artifact_ids)).values()];
    const artifacts_structure = await retrieveArtifactsStructure(
        tracker_structure_map,
        artifacts,
        get_test_execution
    );

    return {
        name: gettext_provider.$gettextInterpolate(
            gettext_provider.$gettext("Test Report %{ milestone_title }"),
            { milestone_title: global_properties.milestone_name }
        ),
        backlog: artifacts_structure.map((artifact) =>
            formatArtifact(
                artifact,
                datetime_locale_information,
                global_properties.base_url,
                global_properties.artifact_links_types
            )
        ),
        traceability_matrix: await getTraceabilityMatrix(campaigns, datetime_locale_information),
    };
}
