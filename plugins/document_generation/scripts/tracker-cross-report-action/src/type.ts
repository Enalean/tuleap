/*
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

import type { ArtifactReportResponseFieldValue } from "@tuleap/plugin-docgen-docx";
import type { ArtifactResponseNoInstance } from "@tuleap/plugin-tracker-rest-api-types";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";

export type ArtifactForCrossReportDocGen = Pick<ArtifactResponseNoInstance, "id" | "values">;

export interface GlobalExportProperties {
    readonly current_project_id: number;
    readonly current_tracker_id: number;
    readonly current_tracker_name: string;
    readonly current_report_id: number;
}

export interface SelectedReport {
    readonly id: number;
    readonly label: string;
}

export interface SelectedTracker {
    readonly id: number;
    readonly label: string;
}

export type ArtifactReportResponseFieldValueWithExtraFields =
    | ArtifactReportResponseFieldValue
    | ArtifactReportExtraFieldValue;

interface ArtifactReportExtraFieldValue {
    field_id: number;
    type: "burndown" | "burnup" | "Encrypted" | "ttmstepexec";
    label: string;
    value: never;
}

export interface LinkedArtifactsResponse {
    collection: ReadonlyArray<ArtifactForCrossReportDocGen>;
}

export interface OrganizedReportsData {
    readonly first_level: OrganizedReportDataLevel;
    readonly second_level?: OrganizedReportDataLevel;
    readonly third_level?: Omit<OrganizedReportDataLevel, "linked_artifacts">;
}

export interface OrganizedReportDataLevel {
    readonly artifact_representations: Map<number, ArtifactForCrossReportDocGen>;
    tracker_name: string;
    linked_artifacts: Map<number, ReadonlyArray<number>>;
}

export class TextCellWithMerges extends TextCell {
    constructor(
        override readonly value: string,
        readonly merges: number,
    ) {
        super(value);
    }
}
