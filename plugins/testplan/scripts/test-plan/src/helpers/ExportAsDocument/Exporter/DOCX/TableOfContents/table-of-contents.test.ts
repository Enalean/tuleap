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
import type { GlobalExportProperties } from "../../../../../type";
import type { VueGettextProvider } from "../../../../vue-gettext-provider";
import { createVueGettextProviderPassthrough } from "../../../../vue-gettext-provider-for-test";

describe("Table of contents", () => {
    let global_export_properties: GlobalExportProperties;
    let gettext_provider: VueGettextProvider;

    beforeEach(() => {
        global_export_properties = {
            platform_name: "My Tuleap Platform",
            platform_logo_url: "platform/logo/url",
            project_name: "ACME",
            user_display_name: "Jean Dupont",
            user_timezone: "UTC",
            user_locale: "en_US",
            milestone_name: "Tuleap 13.3",
            parent_milestone_name: "",
            milestone_url: "/path/to/13.3",
        };

        gettext_provider = createVueGettextProviderPassthrough();
    });

    it("builds a TOC prefilled with main sections", () => {
        const toc = new TableOfContentsPrefilled(gettext_provider, global_export_properties);
        const tree = toc.prepForXml({} as IContext);

        expect(tree).toStrictEqual(TOC_WITH_CONTENT);
    });
});
