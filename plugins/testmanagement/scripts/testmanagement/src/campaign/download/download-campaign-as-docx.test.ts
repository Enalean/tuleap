/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { downloadCampaignAsDocx } from "./download-campaign-as-docx";
import type { Campaign, GlobalExportProperties } from "../../type";

const downloadExportDocumentMock = jest.fn();
jest.mock("../../helpers/ExportAsDocument/download-export-document", () => {
    return {
        downloadExportDocument: downloadExportDocumentMock,
    };
});
const downloadDocxMock = jest.fn();
jest.mock("../../helpers/ExportAsDocument/Exporter/DOCX/download-docx", () => {
    return {
        downloadDocx: downloadDocxMock,
    };
});

describe("downloadCampaignAsDocx", () => {
    it("should download a document", async () => {
        const campaign: Campaign = { label: "Tuleap 13.5", id: 123 } as Campaign;

        await downloadCampaignAsDocx(
            campaign,
            "ACME",
            "https://example.com/logo.gif",
            "Gemini Croquette Contest",
            "Korben Dallas",
            "UTC",
            "en_US",
            "https://example.com/",
            101,
            10,
            [],
        );

        expect(downloadExportDocumentMock).toHaveBeenCalledWith(
            {
                platform_name: "ACME",
                platform_logo_url: "https://example.com/logo.gif",
                project_name: "Gemini Croquette Contest",
                user_display_name: "Korben Dallas",
                user_timezone: "UTC",
                user_locale: "en_US",
                title: "Tuleap 13.5",
                campaign_name: "Tuleap 13.5",
                campaign_url:
                    "https://example.com/plugins/testmanagement/?group_id=101#!/campaigns/123",
                base_url: "https://example.com/",
                artifact_links_types: [],
                testdefinition_tracker_id: 10,
            } as GlobalExportProperties,
            downloadDocxMock,
            campaign,
        );
    });
});
