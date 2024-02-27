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
import * as getters from "./getters";
import type { RootState } from "./type";
import type { TemplateData } from "../type";
import { is_currently_selected_template } from "./getters";

describe("getters", () => {
    describe("is_template_selected", () => {
        it(`Should return false when there is no selected template`, () => {
            const state: RootState = {
                selected_tuleap_template: null,
                selected_company_template: null,
            } as RootState;
            expect(getters.is_template_selected(state)).toBe(false);
        });
        it(`Should return true when a tuleap template is choosen`, () => {
            const state: RootState = {
                selected_tuleap_template: {
                    title: "scrum",
                    description: "scrum desc",
                    id: "scrum_template",
                    glyph: "<svg></svg>",
                    is_built_in: true,
                } as TemplateData,
                selected_company_template: null,
            } as RootState;
            expect(getters.is_template_selected(state)).toBe(true);
        });
        it(`Should return true when a company template is choosen`, () => {
            const state: RootState = {
                selected_tuleap_template: null,
                selected_company_template: {
                    title: "scrum",
                    description: "scrum desc",
                    id: "10",
                    glyph: "<svg></svg>",
                    is_built_in: false,
                } as TemplateData,
            } as RootState;
            expect(getters.is_template_selected(state)).toBe(true);
        });
    });

    describe("has_error", () => {
        it(`Should return false when no error message is stored`, () => {
            const state: RootState = {
                error: null,
            } as RootState;
            expect(getters.has_error(state)).toBe(false);
        });
        it(`Should return true when a template is choosen`, () => {
            const state: RootState = {
                error: "Ho snap!",
            } as RootState;
            expect(getters.has_error(state)).toBe(true);
        });
    });

    describe("is_currently_selected_template", () => {
        let state: RootState, scrum_template: TemplateData;

        beforeEach(() => {
            state = {
                selected_company_template: null,
                selected_tuleap_template: null,
            } as RootState;

            scrum_template = { id: "scrum" } as TemplateData;
        });
        it("should return false when no templates are selected", () => {
            expect(is_currently_selected_template(state)(scrum_template)).toBe(false);
        });

        it("should return true when the provided company template is currently selected", () => {
            state.selected_company_template = scrum_template;
            expect(is_currently_selected_template(state)(scrum_template)).toBe(true);
        });

        it("should return true when the provided tuleap template is currently selected", () => {
            state.selected_tuleap_template = scrum_template;
            expect(is_currently_selected_template(state)(scrum_template)).toBe(true);
        });
    });
    describe("is_advanced_option_selected", () => {
        it(`Should return true when the selected option is the current option`, () => {
            const state: RootState = {
                selected_advanced_option: "from_existing_user_project",
            } as RootState;
            expect(getters.is_advanced_option_selected(state)("from_existing_user_project")).toBe(
                true,
            );
        });
        it(`Should return false when the selected option is not the current option`, () => {
            const state: RootState = {
                selected_advanced_option: null,
            } as RootState;
            expect(getters.is_advanced_option_selected(state)("from_existing_user_project")).toBe(
                false,
            );
        });
    });
});
