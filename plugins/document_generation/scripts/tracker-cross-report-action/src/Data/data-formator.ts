/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { transformFieldValueIntoAFormattedCell } from "./transform-field-value-into-formatted-cell";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import type { TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { ExportSettings } from "../export-document";
import { organizeReportsData } from "./organize-reports-data";
import type { OrganizedReportsData } from "../type";
import { isFieldTakenIntoAccount } from "./field-type-checker";
import { formatHeader } from "./headers-formator";

export interface ReportSection {
    readonly headers?: ReadonlyArray<TextCell>;
    readonly rows?: ReadonlyArray<ReadonlyArray<ReportCell>>;
}

export async function formatData(export_settings: ExportSettings): Promise<ReportSection> {
    const organized_reports_data: OrganizedReportsData = await organizeReportsData(export_settings);

    if (organized_reports_data.artifact_representations.size === 0) {
        return {};
    }

    const artifact_rows: Array<Array<ReportCell>> = [];
    let artifact_value_rows: Array<ReportCell> = [];

    for (const artifact of organized_reports_data.artifact_representations.values()) {
        artifact_value_rows = [];

        for (const field_value of artifact.values) {
            if (!isFieldTakenIntoAccount(field_value)) {
                continue;
            }

            artifact_value_rows.push(transformFieldValueIntoAFormattedCell(field_value));
        }
        artifact_rows.push(artifact_value_rows);
    }

    return {
        headers: formatHeader(organized_reports_data),
        rows: artifact_rows,
    };
}
