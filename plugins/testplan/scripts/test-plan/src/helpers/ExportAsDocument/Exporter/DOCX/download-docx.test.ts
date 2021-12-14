/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import * as trigger_download from "./trigger-blob-download";
import * as image_loader from "@tuleap/plugin-docgen-docx";
import { downloadDocx } from "./download-docx";
import { ImageRun } from "docx";
import { createVueGettextProviderPassthrough } from "../../../vue-gettext-provider-for-test";

describe("download-docx", () => {
    it("generates a docx file from the exported document", async (): Promise<void> => {
        const gettext_provider = createVueGettextProviderPassthrough();
        const trigger_download_spy = jest.spyOn(trigger_download, "triggerBlobDownload");
        trigger_download_spy.mockImplementation((filename: string, blob: Blob) => {
            expect(filename).toBe("Document Title.docx");
            expect(blob.size > 0).toBe(true);
        });
        jest.spyOn(image_loader, "loadImage").mockResolvedValue(
            new ImageRun({
                data: "image_data",
                transformation: {
                    width: 100,
                    height: 100,
                },
            })
        );

        await downloadDocx(
            {
                name: "Document Title",
                backlog: [],
                traceability_matrix: [],
            },
            gettext_provider,
            {
                platform_name: "My Tuleap Platform",
                platform_logo_url: "platform/logo/url",
                project_name: "ACME",
                user_display_name: "Jean Dupont",
                user_timezone: "UTC",
                user_locale: "en_US",
                milestone_name: "Tuleap 13.3",
                parent_milestone_name: "",
                milestone_url: "/path/to/13.3",
                base_url: "https://example.com",
                artifact_links_types: [],
            },
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );

        expect(trigger_download_spy).toHaveBeenCalled();
    });
});
