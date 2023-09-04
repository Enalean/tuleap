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

import type { ExportReport } from "../../Report/report-creator";
import { utils, writeFile } from "xlsx";
import { transformAReportIntoASheet } from "./transform-report-to-xlsx-sheet";
import type { VueGettextProvider } from "../../../vue-gettext-provider";

export function downloadXLSX(
    gettext_provider: VueGettextProvider,
    milestone_title: string,
    report: ExportReport,
): void {
    const book = utils.book_new();
    const sheet = transformAReportIntoASheet(report);
    utils.book_append_sheet(book, sheet);
    writeFile(
        book,
        gettext_provider.interpolate(
            gettext_provider.$gettext("Test Report %{ milestone_title }"),
            { milestone_title },
        ) + ".xlsx",
        { bookSST: true },
    );
}
