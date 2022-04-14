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

import type { ExportSettings } from "../../export-document";
import { generateFilename } from "./file-name-generator";

describe("file-name-generator", () => {
    it("generates the file name with all the selected levels", (): void => {
        const export_settings: ExportSettings = {
            first_level: {
                tracker_name: "Tracker01",
                report_name: "Report01",
            },
            second_level: {
                tracker_name: "Tracker02",
                report_name: "Report02",
            },
            third_level: {
                tracker_name: "Tracker03",
                report_name: "Report03",
            },
        } as ExportSettings;

        const filename = generateFilename(export_settings);

        expect(filename).toBe("Tracker01-Report01-Tracker02-Tracker03.xlsx");
    });
    it("generates the file name with all selected levels 1 and 2", (): void => {
        const export_settings: ExportSettings = {
            first_level: {
                tracker_name: "Tracker01",
                report_name: "Report01",
            },
            second_level: {
                tracker_name: "Tracker02",
                report_name: "Report02",
            },
        } as ExportSettings;

        const filename = generateFilename(export_settings);

        expect(filename).toBe("Tracker01-Report01-Tracker02.xlsx");
    });
    it("generates the file name with only selected level 1", (): void => {
        const export_settings: ExportSettings = {
            first_level: {
                tracker_name: "Tracker01",
                report_name: "Report01",
            },
        } as ExportSettings;

        const filename = generateFilename(export_settings);

        expect(filename).toBe("Tracker01-Report01.xlsx");
    });
});
