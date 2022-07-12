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

import { generateAutofilterRange } from "./autofilter-generator";
import type { ReportSection } from "../../Data/data-formator";
import { EmptyCell, TextCell } from "@tuleap/plugin-docgen-xlsx";

describe("autofilter-generator", () => {
    it("generates the autofilter range", (): void => {
        const formatted_data: ReportSection = {
            headers: {
                tracker_names: [],
                reports_fields_labels: [
                    new TextCell("Field01"),
                    new TextCell("Field02"),
                    new TextCell("Field03"),
                    new TextCell("Field04"),
                ],
            },
            artifacts_rows: [
                [
                    new TextCell("Value01"),
                    new TextCell("Value02"),
                    new TextCell("Value03"),
                    new EmptyCell(),
                ],
                [
                    new TextCell("Value04"),
                    new TextCell("Value05"),
                    new EmptyCell(),
                    new TextCell("Value06"),
                ],
            ],
        };

        const autofilter_range: string = generateAutofilterRange(formatted_data);

        expect(autofilter_range).toBe("A2:D4");
    });
    it("generates empty autofilter range if headers is missing in formatted_data", (): void => {
        const formatted_data: ReportSection = {
            artifacts_rows: [
                [
                    new TextCell("Value01"),
                    new TextCell("Value02"),
                    new TextCell("Value03"),
                    new EmptyCell(),
                ],
                [
                    new TextCell("Value04"),
                    new TextCell("Value05"),
                    new EmptyCell(),
                    new TextCell("Value06"),
                ],
            ],
        };

        const autofilter_range: string = generateAutofilterRange(formatted_data);

        expect(autofilter_range).toBe("");
    });
    it("generates empty autofilter range if artifacts_rows is missing in formatted_data", (): void => {
        const formatted_data: ReportSection = {
            headers: {
                tracker_names: [],
                reports_fields_labels: [
                    new TextCell("Field01"),
                    new TextCell("Field02"),
                    new TextCell("Field03"),
                    new TextCell("Field04"),
                ],
            },
        };

        const autofilter_range: string = generateAutofilterRange(formatted_data);

        expect(autofilter_range).toBe("");
    });

    it("generates empty autofilter range when there is no report fields", (): void => {
        const formatted_data: ReportSection = {
            artifacts_rows: [],
            headers: {
                tracker_names: [],
                reports_fields_labels: [],
            },
        };

        const autofilter_range: string = generateAutofilterRange(formatted_data);

        expect(autofilter_range).toBe("");
    });
});
