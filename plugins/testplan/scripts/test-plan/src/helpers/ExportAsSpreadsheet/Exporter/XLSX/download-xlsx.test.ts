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

import { downloadXLSX } from "./download-xlsx";
import { createVueGettextProviderPassthrough } from "../../../vue-gettext-provider-for-test";
import * as xlsx from "xlsx";
import * as report_transformer from "./transform-report-to-xlsx-sheet";
import type { ExportReport } from "../../Report/report-creator";

describe("download-xlsx", () => {
    it("starts the download of an XLSX document", () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const spyCreateSheet = jest
            .spyOn(report_transformer, "transformAReportIntoASheet")
            .mockReturnValue(actual_xlsx.utils.json_to_sheet([]));

        downloadXLSX(gettext_provider, "Milestone title", {} as ExportReport);

        expect(spyCreateSheet).toHaveBeenCalledTimes(1);
        expect(xlsx.writeFile).toHaveBeenCalledWith(
            expect.anything(),
            "Test Report %{ milestone_title }.xlsx",
            expect.anything(),
        );
    });
});
