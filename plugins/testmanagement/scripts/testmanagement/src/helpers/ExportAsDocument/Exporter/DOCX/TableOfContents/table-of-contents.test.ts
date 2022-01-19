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

import { TableOfContentsPrefilled } from "./table-of-contents";
import type { IContext } from "docx";
import { TOC_WITH_CONTENT } from "./table-of-contents-test-samples";
import type { GenericGlobalExportProperties } from "../../../../../type";
import type { GettextProvider } from "@tuleap/gettext";
import { createGettextProviderPassthrough } from "../../../../create-gettext-provider-passthrough-for-tests";

describe("Table of contents", () => {
    let global_export_properties: GenericGlobalExportProperties;
    let gettext_provider: GettextProvider;

    beforeEach(() => {
        global_export_properties = {
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
        };

        gettext_provider = createGettextProviderPassthrough();
    });

    it("builds a TOC prefilled with main sections", () => {
        const toc = new TableOfContentsPrefilled(gettext_provider, global_export_properties);
        const tree = toc.prepForXml({} as IContext);

        expect(tree).toStrictEqual(TOC_WITH_CONTENT);
    });
});
