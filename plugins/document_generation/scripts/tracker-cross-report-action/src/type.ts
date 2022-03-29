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

export interface TrackerReport {
    readonly id: number;
    readonly name: string;
    readonly is_public: boolean;
}

export interface GlobalExportProperties {
    readonly current_tracker_name: string;
    readonly current_report_id: number;
    readonly current_tracker_reports: ReadonlyArray<TrackerReport>;
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
