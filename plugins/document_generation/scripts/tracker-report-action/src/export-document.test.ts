/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import type { GetText } from "@tuleap/gettext";
import * as document_export_creator from "./DocumentBuilder/create-export-document";
import { startDownloadExportDocument } from "./export-document";
import type { GlobalExportProperties } from "./type";

describe("export-document", () => {
    it("generates the export document, transforms it and then trigger the download", async (): Promise<void> => {
        const document_exporter = vi.fn();
        const export_creator = vi.spyOn(document_export_creator, "createExportDocument");
        export_creator.mockResolvedValue({
            name: "name",
            artifacts: [
                { id: 1, title: "title", short_title: "title", fields: [], containers: [] },
            ],
            traceability_matrix: [],
        });

        await startDownloadExportDocument(
            {} as GlobalExportProperties,
            { locale: "en_US" } as GetText,
            document_exporter,
        );

        expect(export_creator).toHaveBeenCalled();
        expect(document_exporter).toHaveBeenCalled();
    });
});
