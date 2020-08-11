/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

const actual_xlsx = jest.requireActual("xlsx");
jest.mock("xlsx", () => {
    return { ...actual_xlsx, writeFile: jest.fn() };
});

import { createVueGettextProviderPassthrough } from "../vue-gettext-provider-for-test";
import { downloadExportDocument } from "./download-export-document";
import { ExportReport } from "./report-creator";
import * as xlsx from "xlsx";
import * as report_creator from "./report-creator";
import * as report_transformer from "./transform-report-to-xlsx-sheet";

describe("Start download of export document", () => {
    it("generates the report and start the download of the XLSX document", () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const spyCreateReport = jest
            .spyOn(report_creator, "createExportReport")
            .mockReturnValue({} as ExportReport);
        const spyCreateSheet = jest
            .spyOn(report_transformer, "transformAReportIntoASheet")
            .mockReturnValue(actual_xlsx.utils.json_to_sheet([]));

        downloadExportDocument(gettext_provider, "Project", "Milestone", "User Name", [], []);

        expect(spyCreateReport).toHaveBeenCalledTimes(1);
        expect(spyCreateSheet).toHaveBeenCalledTimes(1);
        expect(xlsx.writeFile).toHaveBeenCalledWith(
            expect.anything(),
            "Test Report %{ milestone_title }.xlsx"
        );
    });
});
