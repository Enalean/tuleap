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

import * as document_exporter from "../../tracker-cross-report-action/src/export-document";
import { startDownloadExportAllReportColumnsSpreadsheet } from "./export-report-columns-spreadsheet";

describe("export-report-columns-spreadsheet", () => {
    it("starts the download of the spreadsheet", async () => {
        const spy_start_download = jest
            .spyOn(document_exporter, "downloadXLSXDocument")
            .mockImplementation(async () => {
                // Do nothing
            });

        await startDownloadExportAllReportColumnsSpreadsheet({
            current_tracker_name: "Tracker",
            current_report_id: 12,
            current_report_name: "Report",
            current_renderer_id: 136,
        });

        expect(spy_start_download).toHaveBeenCalled();
    });
});
