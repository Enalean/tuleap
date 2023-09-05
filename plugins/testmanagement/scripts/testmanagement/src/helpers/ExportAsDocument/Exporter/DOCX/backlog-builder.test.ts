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
import type { IContext } from "docx";
import type {
    ExportDocument,
    GenericGlobalExportProperties,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../../../type";
import type { FormattedArtifact } from "@tuleap/plugin-docgen-docx";
import type { GettextProvider } from "@tuleap/gettext";
import { createGettextProviderPassthrough } from "../../../create-gettext-provider-passthrough-for-tests";

describe("buildMilestoneBacklog", () => {
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
            base_url: "http://example.com",
            artifact_links_types: [],
            testdefinition_tracker_id: 10,
        };

        gettext_provider = createGettextProviderPassthrough();
    });

    it("should indicate that there is nothing in the backlog", async () => {
        const document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults> = {
            name: "Test Report",
            backlog: [],
            traceability_matrix: [],
            tests: [],
        };

        const backlog = await buildMilestoneBacklog(
            document,
            gettext_provider,
            global_export_properties,
        );

        const tree = backlog[1].prepForXml({} as IContext);
        expect(JSON.stringify(tree)).toContain("There are no items.");
    });

    it("should display each backlog item", async () => {
        const document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults> = {
            name: "Test Report",
            backlog: [
                {
                    id: 1,
                    title: "Lorem",
                    short_title: "Lorem",
                    fields: [],
                    containers: [],
                } as FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
                {
                    id: 2,
                    title: "Ipsum",
                    short_title: "Ipsum",
                    fields: [],
                    containers: [],
                } as FormattedArtifact<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
            ],
            traceability_matrix: [],
            tests: [],
        };

        const backlog = await buildMilestoneBacklog(
            document,
            gettext_provider,
            global_export_properties,
        );

        expect(JSON.stringify(backlog[1].prepForXml({} as IContext))).toContain("Lorem");
        expect(JSON.stringify(backlog[3].prepForXml({} as IContext))).toContain("Ipsum");
    });
});
