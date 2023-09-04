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

import { createVueGettextProviderPassthrough } from "../vue-gettext-provider-for-test";
import { downloadExportDocument } from "./download-export-document";
import type { ExportReport } from "./Report/report-creator";
import * as report_creator from "./Report/report-creator";

describe("Start download of export document", () => {
    it("generates the report and start the download of the document", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const spyCreateReport = jest
            .spyOn(report_creator, "createExportReport")
            .mockResolvedValue({} as ExportReport);

        const spyStartDownload = jest.fn();

        await downloadExportDocument(
            gettext_provider,
            spyStartDownload,
            "Project",
            "Milestone",
            "User Name",
            [],
            [],
        );

        expect(spyCreateReport).toHaveBeenCalledTimes(1);
        expect(spyStartDownload).toHaveBeenCalledTimes(1);
    });
});
