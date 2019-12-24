/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import mutations from "./mutations";

describe("mutation", () => {
    describe("setSelectedTemplate()", () => {
        it(`stores the tuleap template and make sure the company template is null`, () => {
            const state = {
                selected_tuleap_template: null,
                tuleap_templates: [],
                are_restricted_users_allowed: false,
                are_anonymous_allowed: false,
                project_default_visibility: "",
                error: null,
                is_creating_project: false,
                is_project_approval_required: false,
                trove_categories: [],
                is_description_required: false,
                project_fields: [],
                company_templates: [],
                selected_company_template: {
                    title: "Whole lot company",
                    description: "I have got whole lot",
                    id: "10",
                    glyph: "<svg></svg>",
                    is_built_in: false
                },
                company_name: ""
            };

            const selected_template = {
                title: "scrum template",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true
            };
            mutations.setSelectedTemplate(state, selected_template);
            expect(state.selected_tuleap_template).toStrictEqual(selected_template);
            expect(state.selected_company_template).toBeNull();
        });

        it(`stores the company template and make sure the tuleap template is null`, () => {
            const state = {
                selected_tuleap_template: {
                    title: "scrum template",
                    description: "scrum desc",
                    id: "scrum",
                    glyph: "<svg></svg>",
                    is_built_in: true
                },
                tuleap_templates: [],
                are_restricted_users_allowed: false,
                are_anonymous_allowed: false,
                project_default_visibility: "",
                error: null,
                is_creating_project: false,
                is_project_approval_required: false,
                trove_categories: [],
                is_description_required: false,
                project_fields: [],
                company_templates: [],
                selected_company_template: null,
                company_name: ""
            };

            const selected_template = {
                title: "Whole lot company",
                description: "I have got whole lot",
                id: "10",
                glyph: "<svg></svg>",
                is_built_in: false
            };
            mutations.setSelectedTemplate(state, selected_template);
            expect(state.selected_company_template).toStrictEqual(selected_template);
            expect(state.selected_tuleap_template).toBeNull();
        });
    });
    describe("resetSelectedTemplate() -", () => {
        it("reset the selected templates", () => {
            const state = {
                selected_tuleap_template: {
                    title: "scrum template",
                    description: "scrum desc",
                    id: "scrum",
                    glyph: "<svg></svg>",
                    is_built_in: true
                },
                tuleap_templates: [],
                are_restricted_users_allowed: false,
                are_anonymous_allowed: false,
                project_default_visibility: "",
                error: null,
                is_creating_project: false,
                is_project_approval_required: false,
                trove_categories: [],
                is_description_required: false,
                project_fields: [],
                company_templates: [],
                selected_company_template: {
                    title: "Whole lot company",
                    description: "I have got whole lot",
                    id: "10",
                    glyph: "<svg></svg>",
                    is_built_in: false
                },
                company_name: ""
            };

            mutations.resetSelectedTemplate(state);
            expect(state.selected_tuleap_template).toBeNull();
            expect(state.selected_company_template).toBeNull();
        });
    });
});
