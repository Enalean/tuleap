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
import type { TemplateData } from "../type";
import { useStore } from "./root";
import { createPinia, setActivePinia } from "pinia";

describe("getters", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    describe("is_template_selected", () => {
        it(`Should return false when there is no selected template`, () => {
            const store = useStore();
            store.selected_tuleap_template = null;
            store.selected_company_template = null;
            expect(store.is_template_selected).toBe(false);
        });
        it(`Should return true when a tuleap template is choosen`, () => {
            const store = useStore();
            store.selected_tuleap_template = {
                title: "scrum",
                description: "scrum desc",
                id: "scrum_template",
                glyph: "<svg></svg>",
                is_built_in: true,
            } as TemplateData;
            store.selected_company_template = null;
            expect(store.is_template_selected).toBe(true);
        });
        it(`Should return true when a company template is choosen`, () => {
            const store = useStore();
            store.selected_tuleap_template = null;
            store.selected_company_template = {
                title: "scrum",
                description: "scrum desc",
                id: "10",
                glyph: "<svg></svg>",
                is_built_in: false,
            } as TemplateData;
            expect(store.is_template_selected).toBe(true);
        });
    });

    describe("has_error", () => {
        it(`Should return false when no error message is stored`, () => {
            const store = useStore();
            store.error = null;
            expect(store.has_error).toBe(false);
        });
        it(`Should return true when a template is choosen`, () => {
            const store = useStore();
            store.error = "Ho snap!";
            expect(store.has_error).toBe(true);
        });
    });

    describe("is_currently_selected_template", () => {
        let scrum_template: TemplateData;

        beforeEach(() => {
            scrum_template = { id: "scrum" } as TemplateData;
        });
        it("should return false when no templates are selected", () => {
            const store = useStore();
            store.selected_company_template = null;
            store.selected_tuleap_template = null;
            expect(store.is_currently_selected_template(scrum_template)).toBe(false);
        });

        it("should return true when the provided company template is currently selected", () => {
            const store = useStore();
            store.selected_tuleap_template = null;
            store.selected_company_template = scrum_template;
            expect(store.is_currently_selected_template(scrum_template)).toBe(true);
        });

        it("should return true when the provided tuleap template is currently selected", () => {
            const store = useStore();
            store.selected_company_template = null;
            store.selected_tuleap_template = scrum_template;
            expect(store.is_currently_selected_template(scrum_template)).toBe(true);
        });
    });
    describe("is_advanced_option_selected", () => {
        it(`Should return true when the selected option is the current option`, () => {
            const store = useStore();
            store.selected_company_template = null;
            store.selected_tuleap_template = null;
            store.selected_advanced_option = "from_existing_user_project";
            expect(store.is_advanced_option_selected("from_existing_user_project")).toBe(true);
        });
        it(`Should return false when the selected option is not the current option`, () => {
            const store = useStore();
            store.selected_company_template = null;
            store.selected_tuleap_template = null;
            store.selected_advanced_option = null;
            expect(store.is_advanced_option_selected("from_existing_user_project")).toBe(false);
        });
    });
});
