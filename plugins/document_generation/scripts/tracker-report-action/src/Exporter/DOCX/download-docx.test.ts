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

import * as trigger_download from "../trigger-blob-download";
import type { GetText } from "../../../../../../../src/scripts/tuleap/gettext/gettext-init";
import { downloadDocx } from "./download-docx";

describe("download-docx", () => {
    it("generates a docx file from the exported document", async (): Promise<void> => {
        const gettext_provider = {
            gettext(value: string): string {
                return value;
            },
        } as GetText;
        const trigger_download_spy = jest.spyOn(trigger_download, "triggerBlobDownload");
        trigger_download_spy.mockImplementation((filename: string, blob: Blob) => {
            expect(filename).toBe("Document Title.docx");
            expect(blob.size > 0).toBe(true);
        });

        await downloadDocx(
            {
                name: "Document Title",
                artifacts: [
                    {
                        id: 741,
                        title: "Art title 741",
                        fields: [
                            {
                                field_name: "Field 1",
                                field_value: "Some string",
                            },
                        ],
                        containers: [
                            {
                                name: "Fieldset title",
                                fields: [
                                    {
                                        field_name: "Field 1",
                                        field_value: "Some string",
                                    },
                                ],
                                containers: [],
                            },
                            {
                                name: "Empty fieldset",
                                fields: [],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
            gettext_provider,
            {
                report_id: 123,
                report_name: "Report name",
                report_has_changed: false,
                tracker_shortname: "bug",
                platform_name: "Platform",
                project_name: "Project name",
                tracker_id: 852,
                tracker_name: "Bug",
                user_display_name: "Realname (shortname)",
                user_timezone: "UTC",
                report_url: "https://example.com/plugins/tracker",
            },
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );

        expect(trigger_download_spy).toHaveBeenCalled();
    });
});
