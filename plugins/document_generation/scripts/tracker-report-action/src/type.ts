/*
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

export interface ArtifactReportResponse {
    readonly id: number;
    readonly title: string | null;
    readonly values: ReadonlyArray<ArtifactReportResponseFieldValue>;
}

type ArtifactReportResponseFieldValue =
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
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

export interface ArtifactFieldValue {
    readonly field_name: string;
    readonly field_value: number | string;
}

export interface ExportDocument {
    readonly name: string;
    readonly artifacts: ReadonlyArray<FormattedArtifact>;
}

export interface FormattedArtifact {
    readonly id: number;
    readonly title: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
}
