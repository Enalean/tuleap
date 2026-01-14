/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import type { Properties } from "./type";
import { downloadCSV, downloadDocument } from "@tuleap/plugin-tracker-report-exporter";

export async function startDownloadExportAllColumnsCSV(
    properties: Properties,
    separator: "comma" | "semicolon" | "tab",
    date_format: "month_day_year" | "day_month_year",
): Promise<void> {
    await downloadDocument(
        {
            first_level: {
                tracker_name: properties.current_tracker_name,
                report_id: properties.current_report_id,
                report_name: properties.current_report_name,
                table_renderer_id: properties.current_renderer_id,
                artifact_link_types: [],
                all_columns: true,
            },
            csv_separator: separator,
            date_format,
        },
        downloadCSV,
    );
}
