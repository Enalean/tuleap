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

import { describe, it, expect, vi } from "vitest";
import type { ExportSettings } from "./export-document";
import { downloadXLSXDocument } from "./export-document";
import * as data_formator from "./Data/data-formator";
import type { ReportSection } from "./Data/data-formator";

describe("export-document", () => {
    it("generates the export document and then trigger the download", async (): Promise<void> => {
        const document_exporter = vi.fn();
        const format_data = vi
            .spyOn(data_formator, "formatData")
            .mockResolvedValue({} as ReportSection);

        await downloadXLSXDocument(
            { first_level: { report_id: 1 } } as ExportSettings,
            document_exporter,
        );

        expect(format_data).toHaveBeenCalled();
        expect(document_exporter).toHaveBeenCalled();
    });
});
