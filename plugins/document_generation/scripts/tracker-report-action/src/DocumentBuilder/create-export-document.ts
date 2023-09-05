/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { ExportDocument } from "../type";
import { retrieveReportArtifacts } from "./artifacts-retriever";
import type {
    ArtifactFieldValueStepDefinitionContent,
    ArtifactLinkType,
    DateTimeLocaleInformation,
    FormattedArtifact,
} from "@tuleap/plugin-docgen-docx";
import { createTraceabilityMatrix } from "./create-traceability-matrix";
import {
    formatArtifact,
    formatStepDefinitionField,
    getTestManagementExecution,
    memoize,
} from "@tuleap/plugin-docgen-docx";

export async function createExportDocument(
    report_id: number,
    report_has_changed: boolean,
    report_name: string,
    tracker_id: number,
    tracker_shortname: string,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
): Promise<ExportDocument<ArtifactFieldValueStepDefinitionContent>> {
    const get_test_execution = memoize(getTestManagementExecution);

    const report_artifacts = await retrieveReportArtifacts(
        tracker_id,
        report_id,
        report_has_changed,
        get_test_execution,
    );

    const traceability_matrix = createTraceabilityMatrix(
        report_artifacts,
        datetime_locale_information,
        get_test_execution,
    );

    const artifact_data: FormattedArtifact<ArtifactFieldValueStepDefinitionContent>[] =
        report_artifacts.map((artifact) =>
            formatArtifact(
                artifact,
                datetime_locale_information,
                base_url,
                artifact_links_types,
                formatStepDefinitionField,
            ),
        );

    return {
        name: `${tracker_shortname} - ${report_name}`,
        artifacts: artifact_data,
        traceability_matrix: await traceability_matrix,
    };
}
