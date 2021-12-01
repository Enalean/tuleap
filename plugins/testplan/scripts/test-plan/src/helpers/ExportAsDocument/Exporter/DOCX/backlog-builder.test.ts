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

import { buildMilestoneBacklog } from "./backlog-builder";
import type { VueGettextProvider } from "../../../vue-gettext-provider";
import { createVueGettextProviderPassthrough } from "../../../vue-gettext-provider-for-test";
import type { IContext } from "docx";
import type { BacklogItem, ExportDocument, GlobalExportProperties } from "../../../../type";

describe("buildMilestoneBacklog", () => {
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

    it("should indicate that there is nothing in the backlog", () => {
        const document: ExportDocument = {
            name: "Test Report",
            backlog: [],
        };

        const backlog = buildMilestoneBacklog(document, gettext_provider, global_export_properties);

        const tree = backlog[1].prepForXml({} as IContext);
        expect(JSON.stringify(tree)).toContain("There is no backlog item");
    });

    it("should display each backlog item", () => {
        const document: ExportDocument = {
            name: "Test Report",
            backlog: [{ label: "Lorem" }, { label: "Ipsum" }] as BacklogItem[],
        };

        const backlog = buildMilestoneBacklog(document, gettext_provider, global_export_properties);

        const tree = backlog[1].prepForXml({} as IContext);
        expect(JSON.stringify(tree)).toContain("Lorem");
        expect(JSON.stringify(tree)).toContain("Ipsum");
    });
});
