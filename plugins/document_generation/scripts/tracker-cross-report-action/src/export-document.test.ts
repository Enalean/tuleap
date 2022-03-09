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

import { downloadXLSXDocument } from "./export-document";
import type { GlobalExportProperties } from "./type";
import * as rest_querier from "./rest-querier";
import type { ArtifactResponse } from "./type";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("export-document", () => {
    it("generates the export document and then trigger the download", async (): Promise<void> => {
        const document_exporter = jest.fn();
        const getReportArtifactsMock = jest.spyOn(rest_querier, "getReportArtifacts");

        const artifacts_report_response: ArtifactResponse[] = [
            {
                id: 74,
            },
            {
                id: 4,
            },
        ];
        mockFetchSuccess(getReportArtifactsMock, {
            return_json: {
                artifacts_report_response,
            },
        });

        await downloadXLSXDocument({ report_id: 1 } as GlobalExportProperties, document_exporter);

        expect(getReportArtifactsMock).toHaveBeenCalled();
        expect(document_exporter).toHaveBeenCalled();
    });
});
