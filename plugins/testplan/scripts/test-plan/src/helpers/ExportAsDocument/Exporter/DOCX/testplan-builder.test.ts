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

import { createVueGettextProviderPassthrough } from "../../../vue-gettext-provider-for-test";
import type { IContext } from "docx";
import { buildMilestoneTestPlan } from "./testplan-builder";
import type { GlobalExportProperties } from "../../../../type";
import type { VueGettextProvider } from "../../../vue-gettext-provider";
import type { ExportDocument } from "../../../../type";
import type { FormattedArtifact } from "@tuleap/plugin-docgen-docx/src";
import type { ArtifactFieldValueStepDefinitionContent } from "@tuleap/plugin-docgen-docx/src";

describe("buildMilestoneTestPlan", () => {
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
            base_url: "http://example.com",
            artifact_links_types: [],
            testdefinition_tracker_id: 10,
        };

        gettext_provider = createVueGettextProviderPassthrough();
    });

    it("should display a message if there is no tests", async () => {
        const section = await buildMilestoneTestPlan(
            {
                name: "Tuleap 13.4",
                backlog: [],
                traceability_matrix: [],
                tests: [],
            },
            gettext_provider,
            global_export_properties
        );

        const tree = section[1].prepForXml({} as IContext);
        expect(JSON.stringify(tree)).toContain("There are no tests planned in the milestone.");
    });

    it("should display each test", async () => {
        const document: ExportDocument = {
            name: "Test Report",
            backlog: [],
            traceability_matrix: [],
            tests: [
                {
                    id: 1,
                    title: "Lorem",
                    short_title: "Lorem",
                    fields: [],
                    containers: [],
                } as FormattedArtifact<ArtifactFieldValueStepDefinitionContent>,
                {
                    id: 2,
                    title: "Ipsum",
                    short_title: "Ipsum",
                    fields: [],
                    containers: [],
                } as FormattedArtifact<ArtifactFieldValueStepDefinitionContent>,
            ],
        };

        const backlog = await buildMilestoneTestPlan(
            document,
            gettext_provider,
            global_export_properties
        );

        expect(JSON.stringify(backlog[1].prepForXml({} as IContext))).toContain("Lorem");
        expect(JSON.stringify(backlog[3].prepForXml({} as IContext))).toContain("Ipsum");
    });
});
