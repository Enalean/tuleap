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

import { getAttributeOrThrow } from "@tuleap/dom";
import { showLoaderWhileProcessing } from "./exports/show-loader-processing";

export function setupCSVExport(doc: Document): void {
    setupExportReportColumn(doc);
    setupExportAllColumn(doc);
}

function setupExportReportColumn(doc: Document): void {
    const export_report_columns_button = doc.getElementById(
        "tracker-report-csv-export-report-columns",
    );
    if (export_report_columns_button === null) {
        return;
    }
    export_report_columns_button.addEventListener("click", async (event) => {
        event.preventDefault();

        const properties = JSON.parse(
            getAttributeOrThrow(export_report_columns_button, "data-properties"),
        );
        const separator = getAttributeOrThrow(export_report_columns_button, "data-csv-separator");
        const date_format = getAttributeOrThrow(export_report_columns_button, "data-date-format");

        await showLoaderWhileProcessing(doc, async () => {
            const { startDownloadExportAllReportColumnsCSV } = await import(
                "./exports/export-report-columns-csv"
            );

            await startDownloadExportAllReportColumnsCSV(properties, separator, date_format);
        });
    });
}

function setupExportAllColumn(doc: Document): void {
    const export_all_columns_button = doc.getElementById("tracker-report-csv-export-all-columns");
    if (export_all_columns_button === null) {
        return;
    }
    export_all_columns_button.addEventListener("click", async (event) => {
        event.preventDefault();

        const properties = JSON.parse(
            getAttributeOrThrow(export_all_columns_button, "data-properties"),
        );
        const separator = getAttributeOrThrow(export_all_columns_button, "data-csv-separator");
        const date_format = getAttributeOrThrow(export_all_columns_button, "data-date-format");

        await showLoaderWhileProcessing(doc, async () => {
            const { startDownloadExportAllColumnsCSV } = await import(
                "./exports/export-all-columns-csv"
            );

            await startDownloadExportAllColumnsCSV(properties, separator, date_format);
        });
    });
}
