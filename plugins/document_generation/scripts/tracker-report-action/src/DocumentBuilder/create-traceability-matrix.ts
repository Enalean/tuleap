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

import type {
    ArtifactFieldValueStatus,
    DateTimeLocaleInformation,
    TraceabilityMatrixElement,
} from "../type";
import type {
    ArtifactFromReport,
    ArtifactReportContainer,
    ArtifactReportFieldValue,
} from "./artifacts-retriever";
import { getTestManagementExecution } from "./rest-querier";

export async function createTraceabilityMatrix(
    artifacts: ReadonlyArray<ArtifactFromReport>,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<ReadonlyArray<TraceabilityMatrixElement>> {
    if (!isFeatureExplicitlyEnabled()) {
        return [];
    }
    const possible_elements = await getPossibleMatrixElements(
        artifacts,
        datetime_locale_information
    );

    // Note that the elements are not yet filtered. Elements that do cover an item should not be present in the matrix.
    // Filtering will come in an upcoming contribution
    return possible_elements.flatMap((element) => {
        const matrix_elements: TraceabilityMatrixElement[] = [];
        for (const campaign_id of element.campaigns) {
            matrix_elements.push({
                result: element.result,
                executed_on: element.executed_on,
                executed_by: element.executed_by,
                test: element.test,
                campaign: `#${campaign_id}`,
            });
        }

        return matrix_elements;
    });
}

interface PossibleMatrixElement {
    result: ArtifactFieldValueStatus;
    executed_by: string | null;
    executed_on: string | null;
    test: string;
    campaigns: ReadonlyArray<number>;
}

async function getPossibleMatrixElements(
    artifacts: ReadonlyArray<ArtifactFromReport>,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<ReadonlyArray<Readonly<PossibleMatrixElement>>> {
    const possible_elements = [];
    for (const artifact of artifacts) {
        const fields = getArtifactFieldValues([artifact]);

        const is_artifact_a_step_exec = fields.some((field) => field.type === "ttmstepexec");
        if (!is_artifact_a_step_exec) {
            continue;
        }

        let art_link_field = null;
        for (const field of fields) {
            if (field.type === "art_link") {
                art_link_field = field;
                break;
            }
        }

        if (art_link_field === null) {
            continue;
        }

        const campaigns = art_link_field.reverse_links;
        if (campaigns.length === 0) {
            continue;
        }

        let test_exec = null;
        try {
            test_exec = await getTestManagementExecution(artifact.id);
        } catch (e) {
            continue;
        }

        let submitted_on: string | null = null;
        if (test_exec.previous_result !== null) {
            const submitted_on_date = new Date(test_exec.previous_result.submitted_on);
            const { locale, timezone } = datetime_locale_information;
            submitted_on =
                submitted_on_date.toLocaleDateString(locale, {
                    timeZone: timezone,
                }) +
                " " +
                submitted_on_date.toLocaleTimeString(locale, { timeZone: timezone });
        }

        possible_elements.push({
            result: test_exec.previous_result?.status ?? null,
            executed_by: test_exec.previous_result?.submitted_by.display_name ?? null,
            executed_on: submitted_on,
            test: test_exec.definition.summary,
            campaigns: campaigns.map((campaign) => campaign.id),
        });
    }

    return possible_elements;
}

interface ArtifactSectionContent {
    readonly values: ReadonlyArray<ArtifactReportFieldValue>;
    readonly containers: ReadonlyArray<ArtifactReportContainer>;
}

function getArtifactFieldValues(
    artifact_section_contents: ReadonlyArray<ArtifactSectionContent>
): ReadonlyArray<ArtifactReportFieldValue> {
    return artifact_section_contents.flatMap((artifact_section_content) => {
        return [
            ...artifact_section_content.values,
            ...getArtifactFieldValues(artifact_section_content.containers),
        ];
    });
}

function isFeatureExplicitlyEnabled(): boolean {
    return window.location.hash.includes("matrix");
}
