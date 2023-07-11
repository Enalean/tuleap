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
import * as trigger_download from "../trigger-blob-download";
import * as image_loader from "@tuleap/plugin-docgen-docx";
import type { GetText } from "@tuleap/gettext";
import { downloadDocx } from "./download-docx";
import { ImageRun } from "docx";

describe("download-docx", () => {
    it("generates a docx file from the exported document", async (): Promise<void> => {
        const gettext_provider = {
            gettext(value: string): string {
                return value;
            },
        } as GetText;
        const trigger_download_spy = vi.spyOn(trigger_download, "triggerBlobDownload");
        trigger_download_spy.mockImplementation((filename: string, blob: Blob) => {
            expect(filename).toBe("Document Title.docx");
            expect(blob.size).toBeGreaterThan(0);
        });
        vi.spyOn(image_loader, "loadImage").mockResolvedValue(
            new ImageRun({
                data: "data:image/gif;base64,R0lGODlhAQABAAAAACw=",
                transformation: {
                    width: 100,
                    height: 100,
                },
            })
        );

        await downloadDocx(
            {
                name: "Document Title",
                artifacts: [
                    {
                        id: 741,
                        title: "Art title 741",
                        short_title: "Art title 741",
                        fields: [
                            {
                                content_length: "short",
                                field_name: "Field 1",
                                field_value: "Some string",
                                value_type: "string",
                            },
                            {
                                content_length: "long",
                                content_format: "plaintext",
                                field_name: "Field 2",
                                field_value: "Long text content",
                                value_type: "string",
                            },
                            {
                                content_length: "short",
                                field_name: "Field List 01",
                                field_value: [
                                    {
                                        link_label: "file01.jpg",
                                        link_url: "/plugins/tracker/attachments/file01.jpg",
                                    },
                                    {
                                        link_label: "file02.jpg",
                                        link_url: "/plugins/tracker/attachments/file02.jpg",
                                    },
                                ],
                                value_type: "links",
                            },
                            {
                                field_name: "Artifact links",
                                content_length: "artlinktable",
                                value_type: "string",
                                links: [
                                    {
                                        artifact_id: 123,
                                        html_url: new URL("https://example.com/path/to/123"),
                                        is_linked_artifact_part_of_document: true,
                                        title: "Linked artifact",
                                        type: "",
                                    },
                                ],
                                reverse_links: [
                                    {
                                        artifact_id: 124,
                                        html_url: new URL("https://example.com/path/to/124"),
                                        is_linked_artifact_part_of_document: true,
                                        title: "Reverse linked artifact",
                                        type: "",
                                    },
                                ],
                            },
                        ],
                        containers: [
                            {
                                name: "Fieldset title",
                                fields: [
                                    {
                                        content_length: "short",
                                        field_name: "Field 1",
                                        field_value: "Some string",
                                        value_type: "string",
                                    },
                                    {
                                        content_length: "long",
                                        content_format: "html",
                                        field_name: "Field 3",
                                        field_value: "Long HTML content inside a fieldset",
                                        value_type: "string",
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
                traceability_matrix: [
                    {
                        requirement: "Some requirement",
                        result: "passed",
                        campaign: "Some campaigns",
                        test: {
                            id: 369,
                            title: "Some test",
                        },
                        executed_on: "2021-07-01T00:00:00+02:00",
                        executed_by: "Realname (shortname)",
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
                platform_logo_url: "/themes/common/images/homepage-logo.png",
                project_name: "Project name",
                tracker_id: 852,
                tracker_name: "Bug",
                user_display_name: "Realname (shortname)",
                user_timezone: "UTC",
                report_url: "https://example.com/plugins/tracker",
                report_criteria: {
                    is_in_expert_mode: true,
                    query: "field01 = value01",
                },
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
