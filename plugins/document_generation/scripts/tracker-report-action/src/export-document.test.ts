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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import * as document_export_creator from "./helpers/create-export-document";
import { startDownloadExportDocument } from "./export-document";

jest.mock("@tuleap/tlp-fetch");

describe("export-document", () => {
    it("generates the export document, transforms it and then trigger the download", async (): Promise<void> => {
        const document_exporter = jest.fn();
        const recursive_get_spy = jest.spyOn(tlp_fetch, "recursiveGet");
        recursive_get_spy.mockResolvedValue([]);
        const export_creator = jest.spyOn(document_export_creator, "createExportDocument");
        export_creator.mockReturnValue({ fields: [] });

        await startDownloadExportDocument(12, document_exporter);

        expect(export_creator).toHaveBeenCalled();
        expect(document_exporter).toHaveBeenCalled();
    });
});
