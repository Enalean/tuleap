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

import { showLoaderWhileProcessing } from "./show-loader-processing";

export function setupLinkForTheSpreadsheetExport(): void {
    const generate_spreadsheet_link = document.getElementById(
        "tracker-document-generation-xlsx-all-report-columns",
    );
    if (!generate_spreadsheet_link) {
        throw new Error("Missing generate spreadsheet all report columns button");
    }
    generate_spreadsheet_link.addEventListener("click", async (event): Promise<void> => {
        event.preventDefault();

        if (!generate_spreadsheet_link.dataset.properties) {
            throw new Error("Missing properties dataset");
        }
        const properties = JSON.parse(generate_spreadsheet_link.dataset.properties);

        await showLoaderWhileProcessing(async (): Promise<void> => {
            const { startDownloadExportAllReportColumnsSpreadsheet } = await import(
                "./export-report-columns-spreadsheet"
            );

            await startDownloadExportAllReportColumnsSpreadsheet(properties);
        });
    });
}
