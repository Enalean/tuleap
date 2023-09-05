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
import { downloadXLSXDocument } from "../../tracker-cross-report-action/src/export-document";
import { downloadXLSX } from "../../tracker-cross-report-action/src/Exporter/XLSX/download-xlsx";

interface Properties {
    readonly current_tracker_name: string;
    readonly current_report_id: number;
    readonly current_report_name: string;
    readonly current_renderer_id: number;
}

export async function startDownloadExportAllReportColumnsSpreadsheet(
    properties: Properties,
): Promise<void> {
    await downloadXLSXDocument(
        {
            first_level: {
                tracker_name: properties.current_tracker_name,
                report_id: properties.current_report_id,
                report_name: properties.current_report_name,
                table_renderer_id: properties.current_renderer_id,
                artifact_link_types: [],
            },
        },
        downloadXLSX,
    );
}
