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

import { buildCoverPage } from "./cover-builder";
import * as image_loader from "@tuleap/plugin-docgen-docx";
import type { IContext } from "docx";
import { File, ImageRun } from "docx";
import type { GlobalExportProperties } from "../../../../type";
import type { GettextProvider } from "@tuleap/gettext";
import { createGettextProviderPassthrough } from "../../../create-gettext-provider-passthrough-for-tests";

describe("cover-builder", () => {
    describe("buildCoverPage", () => {
        let context: IContext;
        let global_export_properties: GlobalExportProperties;
        let gettext_provider: GettextProvider;

        beforeEach(() => {
            context = {
                file: new File({ sections: [] }),
            } as IContext;

            global_export_properties = {
                platform_name: "My Tuleap Platform",
                platform_logo_url: "platform/logo/url",
                project_name: "ACME",
                user_display_name: "Jean Dupont",
                user_timezone: "UTC",
                user_locale: "en_US",
                title: "Tuleap 13.3",
                campaign_name: "Tuleap 13.3",
                campaign_url: "/path/to/13.3",
                base_url: "https://example.com",
                artifact_links_types: [],
                testdefinition_tracker_id: null,
            };

            gettext_provider = createGettextProviderPassthrough();
        });

        it("builds a cover page with logo", async () => {
            const one_px_image_as_base64 =
                "R0lGODlhAQABAIABAAAAAP///yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
            const spy_load_image = jest.spyOn(image_loader, "loadImage").mockResolvedValue(
                new ImageRun({
                    data: `data:image/gif;base64,${one_px_image_as_base64}`,
                    transformation: {
                        width: 1,
                        height: 1,
                    },
                }),
            );

            const cover_page = await buildCoverPage(
                gettext_provider,
                global_export_properties,
                "25/11/2021",
            );

            expect(spy_load_image).toHaveBeenCalledTimes(1);
            expect(spy_load_image).toHaveBeenCalledWith("platform/logo/url");

            const tree = cover_page[0].prepForXml(context);

            const exported_file = context.file.Media.Array[0];
            const stream = exported_file.stream as Uint8Array;
            expect(Buffer.from(stream).toString("base64")).toStrictEqual(one_px_image_as_base64);
            expect(JSON.stringify(tree)).toContain(exported_file.fileName);
        });

        it("builds a cover page with current campaign as title", async () => {
            jest.spyOn(image_loader, "loadImage").mockResolvedValue(
                new ImageRun({
                    data: `data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==`,
                    transformation: {
                        width: 1,
                        height: 1,
                    },
                }),
            );

            const cover_page = await buildCoverPage(
                gettext_provider,
                global_export_properties,
                "25/11/2021",
            );

            const tree = cover_page[1].prepForXml(context);
            expect(JSON.stringify(tree)).toContain("Tuleap 13.3");
        });
    });
});
