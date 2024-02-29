/*
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

import { buildProjectPrivacy } from "./privacy-builder";
import type { ProjectProperties } from "../type";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED,
} from "../constant";

let project_properties: ProjectProperties;
let visibility: string;
describe("PrivacyBuilder", () => {
    beforeEach(() => {
        project_properties = {
            shortname: "this-is-a-test",
            label: "this is a test",
            is_public: true,
            description: "",
            categories: [],
            xml_template_name: "scrum",
            fields: [],
            allow_restricted: false,
        };

        visibility = ACCESS_PUBLIC;
    });

    it("Builds xml_template_name", () => {
        const selected_tuleap_template = {
            title: "scrum",
            description: "scrum template",
            id: "scrum",
            glyph: "",
            is_built_in: true,
        };
        const selected_company_template = null;
        const properties_with_privacy = buildProjectPrivacy(
            selected_tuleap_template,
            selected_company_template,
            visibility,
            project_properties,
        );
        expect(properties_with_privacy.xml_template_name).toBe(selected_tuleap_template.id);
    });

    it("Builds default template id", () => {
        const selected_tuleap_template = {
            title: "scrum",
            description: "scrum template",
            id: "100",
            glyph: "",
            is_built_in: true,
        };
        const selected_company_template = null;
        const properties_with_privacy = buildProjectPrivacy(
            selected_tuleap_template,
            selected_company_template,
            visibility,
            project_properties,
        );
        expect(properties_with_privacy.template_id).toBe(parseInt(selected_tuleap_template.id, 10));
    });

    it("Builds selected company template", () => {
        const selected_tuleap_template = null;
        const selected_company_template = {
            title: "company",
            description: "company template",
            id: "2",
            glyph: "",
            is_built_in: false,
        };
        const properties_with_privacy = buildProjectPrivacy(
            selected_tuleap_template,
            selected_company_template,
            visibility,
            project_properties,
        );
        expect(properties_with_privacy.template_id).toBe(
            parseInt(selected_company_template.id, 10),
        );
    });

    it.each([
        [ACCESS_PUBLIC, true, false],
        [ACCESS_PRIVATE, false, true],
        [ACCESS_PUBLIC_UNRESTRICTED, true, true],
        [ACCESS_PRIVATE_WO_RESTRICTED, false, false],
    ])("Builds %s visibility", (visibility, is_public, allow_restricted) => {
        const selected_tuleap_template = {
            title: "scrum",
            description: "scrum template",
            id: "scrum",
            glyph: "",
            is_built_in: true,
        };
        const selected_company_template = null;

        const properties_with_privacy = buildProjectPrivacy(
            selected_tuleap_template,
            selected_company_template,
            visibility,
            project_properties,
        );

        expect(properties_with_privacy.is_public).toBe(is_public);
        expect(properties_with_privacy.allow_restricted).toBe(allow_restricted);
    });
});
