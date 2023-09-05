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
import { downloadDocx } from "./download-docx";
import { createGettextProviderPassthrough } from "../../../create-gettext-provider-passthrough-for-tests";

describe("download-docx", () => {
    it("generates a docx file from the exported document", async (): Promise<void> => {
        const gettext_provider = createGettextProviderPassthrough();
        const trigger_download_spy = jest.spyOn(trigger_download, "triggerBlobDownload");
        trigger_download_spy.mockImplementation((filename: string, blob: Blob) => {
            expect(filename).toBe("Document Title.docx");
            expect(blob.size).toBeGreaterThan(0);
        });

        await downloadDocx(
            {
                name: "Document Title",
                backlog: [],
                traceability_matrix: [],
                tests: [],
            },
            gettext_provider,
            {
                platform_name: "My Tuleap Platform",
                platform_logo_url: "platform/logo/url",
                project_name: "ACME",
                user_display_name: "Jean Dupont",
                user_timezone: "UTC",
                user_locale: "en_US",
                title: "Tuleap 13.3",
                base_url: "https://example.com",
                artifact_links_types: [],
                testdefinition_tracker_id: 10,
            },
            {
                locale: "en-US",
                timezone: "UTC",
            },
            () => Promise.resolve([]),
        );

        expect(trigger_download_spy).toHaveBeenCalled();
    });
});
