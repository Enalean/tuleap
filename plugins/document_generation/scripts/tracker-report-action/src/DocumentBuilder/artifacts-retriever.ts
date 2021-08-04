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

import { get, recursiveGet } from "@tuleap/tlp-fetch";

export interface ArtifactFromReport extends ArtifactReportResponse {
    values: ReadonlyArray<ArtifactReportFieldValue>;
}

export async function retrieveReportArtifacts(
    tracker_id: number,
    report_id: number,
    report_has_changed: boolean
): Promise<ReadonlyArray<ArtifactFromReport>> {
    const fields_structure_promise = retrieveFieldsStructure(tracker_id);
    const report_artifacts: ArtifactReportResponse[] = await recursiveGet(
        `/api/v1/tracker_reports/${encodeURIComponent(report_id)}/artifacts`,
        {
            params: {
                values: "all",
                with_unsaved_changes: report_has_changed,
                limit: 50,
            },
        }
    );

    const fields_structure = await fields_structure_promise;

    const report_artifacts_with_additional_info: ArtifactFromReport[] = [];

    for (const report_artifact of report_artifacts) {
        const values_with_additional_information = report_artifact.values.map(
            (value: ArtifactReportResponseFieldValue): ArtifactReportFieldValue => {
                const field_structure = fields_structure.get(value.field_id);
                switch (value.type) {
                    case "date":
                    case "lud":
                    case "subon":
                        if (field_structure && field_structure.type === value.type) {
                            return {
                                ...value,
                                is_time_displayed: field_structure.is_time_displayed,
                            };
                        }
                        return { ...value, is_time_displayed: true };
                    default:
                        return value;
                }
            }
        );
        report_artifacts_with_additional_info.push({
            ...report_artifact,
            values: values_with_additional_information,
        });
    }

    return report_artifacts_with_additional_info;
}

async function retrieveFieldsStructure(
    tracker_id: number
): Promise<ReadonlyMap<number, FieldsStructure>> {
    const tracker_structure_response = await get(
        `/api/v1/trackers/${encodeURIComponent(tracker_id)}`
    );
    const tracker_structure: TrackerDefinition = await tracker_structure_response.json();

    const fields_map: Map<number, FieldsStructure> = new Map();

    for (const field of tracker_structure.fields) {
        switch (field.type) {
            case "date":
            case "lud":
            case "subon":
                fields_map.set(field.field_id, field);
                break;
            default:
        }
    }

    return fields_map;
}

export interface ArtifactReportResponse {
    readonly id: number;
    readonly title: string | null;
    readonly values: ReadonlyArray<ArtifactReportResponseFieldValue>;
}

export type ArtifactReportFieldValue =
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
    | (ArtifactReportResponseDateFieldValue & { is_time_displayed: boolean })
    | ArtifactReportResponseComputedFieldValue;

type ArtifactReportResponseFieldValue =
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
    | ArtifactReportResponseDateFieldValue
    | ArtifactReportResponseComputedFieldValue;

interface ArtifactReportResponseNumericFieldValue {
    field_id: number;
    type: "aid" | "atid" | "int" | "float" | "priority";
    label: string;
    value: number | null;
}

interface ArtifactReportResponseStringFieldValue {
    field_id: number;
    type: "string";
    label: string;
    value: string | null;
}

interface ArtifactReportResponseDateFieldValue {
    field_id: number;
    type: "date" | "lud" | "subon";
    label: string;
    value: string | null;
}

interface ArtifactReportResponseComputedFieldValue {
    field_id: number;
    type: "computed";
    label: string;
    value: number | null;
    manual_value: number | null;
    is_autocomputed: boolean;
}

export interface ArtifactReportResponseUnknownFieldValue {
    field_id: number;
    type: never;
    label: string;
    value: never;
}

type FieldsStructure = UnknownFieldStructure | DateFieldStructure;

interface BaseFieldStructure {
    field_id: number;
}

interface UnknownFieldStructure extends BaseFieldStructure {
    type: never;
}

interface DateFieldStructure extends BaseFieldStructure {
    type: "date" | "lud" | "subon";
    is_time_displayed: boolean;
}

export interface TrackerDefinition {
    fields: ReadonlyArray<FieldsStructure>;
}
