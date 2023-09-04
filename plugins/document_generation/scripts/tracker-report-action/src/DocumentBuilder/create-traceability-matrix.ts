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

import type { TraceabilityMatrixElement } from "../type";
import type {
    ArtifactFieldValueStatus,
    ArtifactFromReport,
    ArtifactReportContainer,
    ArtifactReportFieldValue,
    ArtifactResponse,
    DateTimeLocaleInformation,
    getTestManagementExecution,
} from "@tuleap/plugin-docgen-docx";
import { getArtifacts } from "@tuleap/plugin-docgen-docx";

export async function createTraceabilityMatrix(
    artifacts: ReadonlyArray<ArtifactFromReport>,
    datetime_locale_information: DateTimeLocaleInformation,
    get_test_execution: typeof getTestManagementExecution,
): Promise<ReadonlyArray<TraceabilityMatrixElement>> {
    const elements = await getMatrixElements(
        artifacts,
        datetime_locale_information,
        get_test_execution,
    );
    const campaigns = await getCampaignsFromRawElements(elements);

    return elements.flatMap((element) => {
        const matrix_elements: TraceabilityMatrixElement[] = [];
        for (const campaign_id of element.campaigns) {
            const campaign_default_title = `#${campaign_id}`;
            const campaign = campaigns.get(campaign_id);
            matrix_elements.push({
                requirement: element.requirement,
                result: element.result,
                executed_on: element.executed_on,
                executed_by: element.executed_by,
                test: element.test,
                campaign: campaign?.title ?? campaign_default_title,
            });
        }

        return matrix_elements;
    });
}

interface RawMatrixElement {
    requirement: string;
    result: ArtifactFieldValueStatus;
    executed_by: string | null;
    executed_on: string | null;
    test: {
        id: number;
        title: string;
    };
    campaigns: ReadonlyArray<number>;
}

async function getMatrixElements(
    artifacts: ReadonlyArray<ArtifactFromReport>,
    datetime_locale_information: DateTimeLocaleInformation,
    get_test_execution: typeof getTestManagementExecution,
): Promise<ReadonlyArray<Readonly<RawMatrixElement>>> {
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
            test_exec = await get_test_execution(artifact.id);
        } catch (e) {
            continue;
        }

        if (test_exec.definition.all_requirements.length === 0) {
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

        for (const { id, title } of test_exec.definition.all_requirements) {
            possible_elements.push({
                requirement: title ?? `#${id}`,
                result: test_exec.previous_result?.status ?? null,
                executed_by: test_exec.previous_result?.submitted_by.display_name ?? null,
                executed_on: submitted_on,
                test: {
                    id: artifact.id,
                    title: test_exec.definition.summary,
                },
                campaigns: campaigns.map((campaign) => campaign.id),
            });
        }
    }

    return possible_elements;
}

interface ArtifactSectionContent {
    readonly values: ReadonlyArray<ArtifactReportFieldValue>;
    readonly containers: ReadonlyArray<ArtifactReportContainer>;
}

function getArtifactFieldValues(
    artifact_section_contents: ReadonlyArray<ArtifactSectionContent>,
): ReadonlyArray<ArtifactReportFieldValue> {
    return artifact_section_contents.flatMap((artifact_section_content) => {
        return [
            ...artifact_section_content.values,
            ...getArtifactFieldValues(artifact_section_content.containers),
        ];
    });
}

async function getCampaignsFromRawElements(
    elements: ReadonlyArray<RawMatrixElement>,
): Promise<ReadonlyMap<number, ArtifactResponse>> {
    const campaign_ids = new Set(elements.flatMap((element) => element.campaigns));

    try {
        return await getArtifacts(campaign_ids);
    } catch (e) {
        return new Map();
    }
}
